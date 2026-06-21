<?php

namespace App\Services;

use App\Models\ParentStudentLink;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ParentLinkService
{
    /**
     * Cache key prefix untuk invite codes
     */
    const CACHE_PREFIX = 'parent_invite_';
    
    /**
     * Durasi cache invite code (24 jam)
     */
    const CACHE_TTL = 1440;

    /**
     * Generate 6 digit alphanumeric invite code
     */
    public function generateInviteCode(User $student): string
    {
        do {
            $code = strtoupper(Str::random(6));
            $cacheKey = self::CACHE_PREFIX . $code;
        } while (Cache::has($cacheKey) || $this->codeExistsInDatabase($code));

        // Simpan di cache dengan data student_id
        Cache::put(
            self::CACHE_PREFIX . $code,
            ['student_id' => $student->id],
            self::CACHE_TTL
        );

        return $code;
    }

    /**
     * Cek apakah code sudah ada di database (untuk link yang pending)
     */
    private function codeExistsInDatabase(string $code): bool
    {
        // Kita simpan code di cache saja, bukan di database
        // Jadi ini selalu false
        return false;
    }

    /**
     * Validasi dan proses invite code oleh orang tua
     */
    public function processInviteCode(User $parent, string $code): array
    {
        $code = strtoupper(trim($code));
        $cacheKey = self::CACHE_PREFIX . $code;

        // Cek cache
        $cachedData = Cache::get($cacheKey);
        
        if (!$cachedData) {
            return [
                'success' => false,
                'message' => 'Kode undangan tidak valid atau sudah kadaluarsa.',
                'error_code' => 'INVALID_CODE'
            ];
        }

        $studentId = $cachedData['student_id'];
        $student = User::find($studentId);

        if (!$student) {
            return [
                'success' => false,
                'message' => 'Siswa tidak ditemukan.',
                'error_code' => 'STUDENT_NOT_FOUND'
            ];
        }

        // Cek apakah sudah ada link aktif antara parent dan student
        $existingLink = ParentStudentLink::where('parent_id', $parent->id)
            ->where('student_id', $student->id)
            ->whereIn('status', [ParentStudentLink::STATUS_PENDING, ParentStudentLink::STATUS_ACTIVE])
            ->first();

        if ($existingLink) {
            if ($existingLink->status === ParentStudentLink::STATUS_ACTIVE) {
                return [
                    'success' => false,
                    'message' => 'Anda sudah terhubung dengan siswa ini.',
                    'error_code' => 'ALREADY_LINKED'
                ];
            } elseif ($existingLink->status === ParentStudentLink::STATUS_PENDING) {
                return [
                    'success' => false,
                    'message' => 'Permintaan tautan sudah pending menunggu persetujuan.',
                    'error_code' => 'PENDING_APPROVAL'
                ];
            }
        }

        // Cek batas maksimal 5 anak untuk orang tua
        $activeChildrenCount = ParentStudentLink::where('parent_id', $parent->id)
            ->where('status', ParentStudentLink::STATUS_ACTIVE)
            ->count();

        if ($activeChildrenCount >= 5) {
            return [
                'success' => false,
                'message' => 'Batas maksimal 5 anak telah tercapai.',
                'error_code' => 'MAX_CHILDREN_REACHED'
            ];
        }

        // Buat link dengan status pending
        DB::beginTransaction();
        try {
            $link = ParentStudentLink::create([
                'parent_id' => $parent->id,
                'student_id' => $student->id,
                'status' => ParentStudentLink::STATUS_PENDING,
                'invited_by' => $parent->id,
            ]);

            // Hapus dari cache setelah berhasil dibuat
            Cache::forget($cacheKey);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Permintaan tautan berhasil dikirim. Menunggu persetujuan siswa.',
                'link' => $link,
                'error_code' => null
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses permintaan.',
                'error_code' => 'PROCESS_ERROR'
            ];
        }
    }

    /**
     * Approve link oleh siswa atau admin
     */
    public function approveLink(ParentStudentLink $link, User $approver): array
    {
        if (!$link->isPending()) {
            return [
                'success' => false,
                'message' => 'Link hanya bisa diapprove jika statusnya pending.',
                'error_code' => 'NOT_PENDING'
            ];
        }

        // Cek batas maksimal 5 anak lagi sebelum approve
        $activeChildrenCount = ParentStudentLink::where('parent_id', $link->parent_id)
            ->where('status', ParentStudentLink::STATUS_ACTIVE)
            ->count();

        if ($activeChildrenCount >= 5) {
            return [
                'success' => false,
                'message' => 'Batas maksimal 5 anak telah tercapai.',
                'error_code' => 'MAX_CHILDREN_REACHED'
            ];
        }

        DB::beginTransaction();
        try {
            $link->approve();

            // Kirim notifikasi ke orang tua
            $this->sendStatusChangeNotification($link, 'approved');

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tautan berhasil disetujui.',
                'link' => $link,
                'error_code' => null
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyetujui tautan.',
                'error_code' => 'APPROVE_ERROR'
            ];
        }
    }

    /**
     * Revoke link oleh siswa atau admin
     */
    public function revokeLink(ParentStudentLink $link, User $revoker): array
    {
        if (!$link->isActive()) {
            return [
                'success' => false,
                'message' => 'Link hanya bisa direvoke jika statusnya active.',
                'error_code' => 'NOT_ACTIVE'
            ];
        }

        DB::beginTransaction();
        try {
            $link->revoke();

            // Kirim notifikasi ke orang tua
            $this->sendStatusChangeNotification($link, 'revoked');

            DB::commit();

            return [
                'success' => true,
                'message' => 'Tautan berhasil dicabut.',
                'link' => $link,
                'error_code' => null
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan saat mencabut tautan.',
                'error_code' => 'REVOKE_ERROR'
            ];
        }
    }

    /**
     * Dapatkan semua pending links untuk seorang siswa
     */
    public function getPendingLinksForStudent(User $student)
    {
        return ParentStudentLink::with('parent')
            ->where('student_id', $student->id)
            ->where('status', ParentStudentLink::STATUS_PENDING)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Dapatkan semua active links untuk seorang siswa
     */
    public function getActiveLinksForStudent(User $student)
    {
        return ParentStudentLink::with('parent')
            ->where('student_id', $student->id)
            ->where('status', ParentStudentLink::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Dapatkan semua active links untuk seorang orang tua
     */
    public function getActiveLinksForParent(User $parent)
    {
        return ParentStudentLink::with('student')
            ->where('parent_id', $parent->id)
            ->where('status', ParentStudentLink::STATUS_ACTIVE)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Dapatkan jumlah anak aktif untuk seorang orang tua
     */
    public function getActiveChildrenCount(User $parent): int
    {
        return ParentStudentLink::where('parent_id', $parent->id)
            ->where('status', ParentStudentLink::STATUS_ACTIVE)
            ->count();
    }

    /**
     * Kirim notifikasi saat status berubah
     */
    private function sendStatusChangeNotification(ParentStudentLink $link, string $newStatus): void
    {
        $parent = $link->parent;
        $student = $link->student;

        if ($newStatus === 'approved') {
            Notification::create([
                'user_id' => $parent->id,
                'type' => 'parent_link_approved',
                'title' => 'Tautan Orang Tua-Siswa Disetujui',
                'message' => "Tautan Anda dengan {$student->full_name} telah disetujui. Anda sekarang dapat memantau perkembangan belajar mereka.",
                'channel' => 'in_app',
                'status' => 'unread',
                'action_url' => '/parent/dashboard',
            ]);

            // Bisa tambahkan email notification di sini
            // Mail::to($parent->email)->send(new ParentLinkApprovedMail($link));
        } elseif ($newStatus === 'revoked') {
            Notification::create([
                'user_id' => $parent->id,
                'type' => 'parent_link_revoked',
                'title' => 'Tautan Orang Tua-Siswa Dicabut',
                'message' => "Tautan Anda dengan {$student->full_name} telah dicabut. Anda tidak lagi dapat mengakses informasi mereka.",
                'channel' => 'in_app',
                'status' => 'unread',
                'action_url' => '/parent/dashboard',
            ]);

            // Bisa tambahkan email notification di sini
            // Mail::to($parent->email)->send(new ParentLinkRevokedMail($link));
        }
    }

    /**
     * Hapus invite code dari cache
     */
    public function clearInviteCode(string $code): void
    {
        Cache::forget(self::CACHE_PREFIX . strtoupper(trim($code)));
    }
}
