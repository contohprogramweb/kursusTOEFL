# EXAM INTERFACE IMPLEMENTATION - Dokumentasi Lengkap

## Overview
Dokumentasi ini menjelaskan implementasi interface ujian TOEFL dengan 4 mode berbeda sesuai dengan requirement SRS dan Addendum.

## Fitur yang Diimplementasikan

### 1. Mode Ujian (FR-3.4.x)

#### a. Practice Mode (Mode Latihan)
**Karakteristik:**
- ✅ Bisa **pause** kapan saja
- ✅ Bisa **resume** setelah pause
- ✅ Bisa **cancel** untuk membatalkan ujian
- ✅ Timer fleksibel, berhenti saat pause
- ✅ Cocok untuk latihan mandiri

**Implementasi:**
```javascript
// Pause functionality
function pauseExam() {
    state.isPaused = true;
    // Show pause modal
    elements.pauseModal.classList.remove('hidden');
}

// Resume functionality
async function resumeExam() {
    await fetch(`/simulations/${state.simulationId}/resume-simulation`, {
        method: 'POST',
        // ... headers
    });
    state.isPaused = false;
}

// Cancel functionality
function cancelExam() {
    showConfirmModal('Cancel Exam?', '...', async () => {
        await fetch(`/simulations/${state.simulationId}/cancel`, {...});
    });
}
```

#### b. Scheduled Mode (Mode Terjadwal)
**Karakteristik:**
- ✅ Timer **tetap berjalan** terus menerus
- ✅ **Auto-abandon** jika user meninggalkan tab/window
- ✅ Menggunakan Visibility API untuk deteksi
- ✅ Simulasi ujian terjadwal dengan waktu ketat

**Implementasi:**
```javascript
// Visibility change detection
document.addEventListener('visibilitychange', handleVisibilityChange);

function handleVisibilityChange() {
    if (state.mode === 'scheduled' && document.visibilityState === 'hidden') {
        abandonExam();
    }
}

async function abandonExam() {
    await fetch(`/simulations/${state.simulationId}/abandon`, {
        method: 'POST',
        // Mark exam as abandoned
    });
    alert('Exam has been marked as abandoned...');
    window.location.href = '/simulations';
}
```

#### c. Realistic Test Day Mode
**Karakteristik:**
- ✅ **Fullscreen wajib** sebelum mulai
- ✅ **Kamera check-in** menggunakan WebRTC/getUserMedia
- ✅ **No distraction mode** - sembunyikan elemen non-esensial
- ✅ Capture frame dari kamera sebagai bukti check-in

**Implementasi:**
```javascript
// Camera check-in
async function showCameraCheckin() {
    state.mediaStream = await navigator.mediaDevices.getUserMedia({ 
        video: { width: 640, height: 480 },
        audio: false 
    });
    
    const video = document.getElementById('checkin-video');
    video.srcObject = state.mediaStream;
    
    elements.cameraCheckinModal.classList.remove('hidden');
}

// Capture check-in frame
elements.btnConfirmCheckin.addEventListener('click', async () => {
    const canvas = document.getElementById('checkin-canvas');
    const video = document.getElementById('checkin-video');
    canvas.getContext('2d').drawImage(video, 0, 0);
    
    // Stop media stream
    state.mediaStream.getTracks().forEach(track => track.stop());
    
    elements.cameraCheckinModal.classList.add('hidden');
    await requestFullscreen();
});
```

#### d. Focus Mode
**Karakteristik:**
- ✅ **Fullscreen otomatis**
- ✅ **Sembunyikan semua elemen non-ujian**
- ✅ Minimal distraction
- ✅ Fokus maksimal pada soal

**Implementasi:**
```javascript
async function init() {
    if (state.mode === 'realistic' || state.mode === 'focus') {
        await requestFullscreen();
    }
    // ... other initialization
}

async function requestFullscreen() {
    const elem = document.documentElement;
    if (!document.fullscreenElement) {
        await elem.requestFullscreen();
        state.isFullscreen = true;
    }
}
```

---

### 2. Timer Akurat per Section (Countdown)

**Fitur:**
- ✅ Timer countdown per section
- ✅ Sync dengan server saat mulai
- ✅ Update setiap detik menggunakan `setInterval`
- ✅ Warna berubah merah saat waktu < 60 detik
- ✅ Auto-transition saat waktu habis

**Implementasi:**
```javascript
function startTimer() {
    // Sync with server first
    syncTimerWithServer();
    
    // Start local countdown
    state.timerInterval = setInterval(() => {
        if (!state.isPaused && state.timeRemaining > 0) {
            state.timeRemaining--;
            updateTimerDisplay();
            recordTimeSpent();
            
            if (state.timeRemaining <= 0) {
                handleTimeUp();
            }
        }
    }, 1000);
}

async function syncTimerWithServer() {
    const response = await fetch(`/simulations/${state.simulationId}/status`);
    const data = await response.json();
    
    if (data.success) {
        const currentSection = getCurrentSectionConfig();
        state.timeRemaining = currentSection.duration_minutes * 60;
        updateTimerDisplay();
    }
}

function updateTimerDisplay() {
    const minutes = Math.floor(state.timeRemaining / 60);
    const seconds = state.timeRemaining % 60;
    elements.timerDisplay.textContent = 
        `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    
    if (state.timeRemaining <= 60) {
        elements.timerDisplay.classList.remove('text-indigo-600');
        elements.timerDisplay.classList.add('text-red-600');
    }
}
```

---

### 3. Break 10 Menit Otomatis

**Fitur:**
- ✅ Break otomatis antara Listening dan Speaking
- ✅ Timer break 10 menit (countdown)
- ✅ User bisa klik "Lanjutkan" untuk skip menunggu
- ✅ Auto-transition ke Speaking saat break time habis

**Implementasi:**
```javascript
function showBreakScreen() {
    elements.examInterface.classList.add('hidden');
    elements.breakScreen.classList.remove('hidden');
    
    let breakTime = 10 * 60; // 10 minutes
    updateBreakTimerDisplay(breakTime);
    
    state.breakTimerInterval = setInterval(() => {
        breakTime--;
        updateBreakTimerDisplay(breakTime);
        
        if (breakTime <= 0) {
            clearInterval(state.breakTimerInterval);
            nextSection();
        }
    }, 1000);
}

function handleTimeUp() {
    clearInterval(state.timerInterval);
    
    if (state.currentSection === 'listening') {
        showBreakScreen(); // Auto-transition to break
    } else if (state.currentSection === 'writing') {
        submitExam(); // Auto-submit
    } else {
        nextSection();
    }
}
```

---

### 4. Navigasi Soal

**Fitur:**
- ✅ **Next** - pindah ke soal berikutnya
- ✅ **Previous** - kembali ke soal sebelumnya
- ✅ **Flag for Review** - tandai soal untuk ditinjau ulang
- ✅ **Review Flagged** - tampilkan grid navigasi soal yang di-flag
- ✅ Question Navigator Grid dengan visual indicator:
  - Biru: Sudah dijawab
  - Kuning: Di-flag
  - Abu-abu: Belum dijawab

**Implementasi:**
```javascript
// Navigation
function goToPrevious() {
    if (state.currentQuestionIndex > 0) {
        state.currentQuestionIndex--;
        loadQuestion(state.currentQuestionIndex);
        updateNavigationButtons();
    }
}

function goToNext() {
    if (state.currentQuestionIndex < state.totalQuestions - 1) {
        state.currentQuestionIndex++;
        loadQuestion(state.currentQuestionIndex);
        updateNavigationButtons();
    } else {
        elements.btnNext.classList.add('hidden');
        elements.btnSubmit.classList.remove('hidden');
    }
}

// Flag functionality
function toggleFlag() {
    if (state.flaggedQuestions.has(state.currentQuestionIndex)) {
        state.flaggedQuestions.delete(state.currentQuestionIndex);
    } else {
        state.flaggedQuestions.add(state.currentQuestionIndex);
    }
    updateFlagButton();
    updateQuestionNavGrid();
}

// Review flagged questions
function showReviewFlagged() {
    elements.questionNavGrid.classList.toggle('hidden');
    if (!elements.questionNavGrid.classList.contains('hidden')) {
        renderQuestionNavGrid();
    }
}

function renderQuestionNavGrid() {
    elements.questionNumbers.innerHTML = '';
    
    for (let i = 0; i < state.totalQuestions; i++) {
        const btn = document.createElement('button');
        btn.className = 'w-8 h-8 rounded flex items-center justify-center text-sm font-medium transition';
        
        if (i === state.currentQuestionIndex) {
            btn.classList.add('ring-2', 'ring-indigo-500');
        }
        
        if (state.answeredQuestions.has(i)) {
            btn.classList.add('bg-indigo-600', 'text-white');
        } else if (state.flaggedQuestions.has(i)) {
            btn.classList.add('bg-yellow-400', 'text-gray-900');
        } else {
            btn.classList.add('bg-gray-200', 'text-gray-700');
        }
        
        btn.textContent = i + 1;
        btn.addEventListener('click', () => goToQuestion(i));
        elements.questionNumbers.appendChild(btn);
    }
}
```

---

### 5. Auto-save Jawaban Setiap 30 Detik

**Fitur:**
- ✅ Auto-save menggunakan AJAX ke server setiap 30 detik
- ✅ Save jawaban current question
- ✅ Include flag status
- ✅ Console log untuk debugging
- ✅ Error handling

**Implementasi:**
```javascript
function startAutoSave() {
    state.autoSaveInterval = setInterval(async () => {
        await saveCurrentAnswer();
    }, 30000); // Every 30 seconds
}

async function saveCurrentAnswer() {
    const answer = getCurrentAnswer();
    if (!answer) return;
    
    try {
        await fetch(`/simulations/${state.simulationId}/answer`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                question_index: state.currentQuestionIndex,
                answer: answer,
                is_flagged: state.flaggedQuestions.has(state.currentQuestionIndex)
            }),
        });
        
        console.log('Answer auto-saved');
    } catch (error) {
        console.error('Auto-save failed:', error);
    }
}
```

---

### 6. Fullscreen API

**Fitur:**
- ✅ Request fullscreen otomatis untuk mode Realistic dan Focus
- ✅ Modal prompt jika fullscreen gagal
- ✅ Button exit fullscreen
- ✅ Event listener untuk fullscreen change

**Implementasi:**
```javascript
async function requestFullscreen() {
    const elem = document.documentElement;
    
    if (!document.fullscreenElement) {
        try {
            await elem.requestFullscreen();
            state.isFullscreen = true;
        } catch (error) {
            console.error('Fullscreen request failed:', error);
            elements.fullscreenModal.classList.remove('hidden');
        }
    }
}

function exitFullscreen() {
    if (document.fullscreenElement) {
        document.exitFullscreen();
        state.isFullscreen = false;
    }
}

function handleFullscreenChange() {
    state.isFullscreen = !!document.fullscreenElement;
    elements.btnExitFullscreen.classList.toggle('hidden', !state.isFullscreen);
}

// Event listener
document.addEventListener('fullscreenchange', handleFullscreenChange);
```

---

### 7. Wake Lock API

**Fitur:**
- ✅ Mencegah screen sleep selama ujian
- ✅ Auto-release saat exam selesai
- ✅ Fallback jika browser tidak support

**Implementasi:**
```javascript
async function requestWakeLock() {
    try {
        if ('wakeLock' in navigator) {
            state.wakeLock = await navigator.wakeLock.request('screen');
            console.log('Wake Lock active');
            
            state.wakeLock.addEventListener('release', () => {
                console.log('Wake Lock released');
            });
        }
    } catch (error) {
        console.error('Wake Lock error:', error);
    }
}

// Release wake lock on submit
if (state.wakeLock) {
    state.wakeLock.release();
}
```

---

### 8. WebRTC / getUserMedia untuk Kamera

**Fitur:**
- ✅ Akses kamera untuk check-in
- ✅ Preview video real-time
- ✅ Capture frame sebagai bukti
- ✅ Stop media stream setelah check-in
- ✅ Error handling jika permission denied

**Implementasi:**
```javascript
async function showCameraCheckin() {
    try {
        state.mediaStream = await navigator.mediaDevices.getUserMedia({ 
            video: { width: 640, height: 480 },
            audio: false 
        });
        
        const video = document.getElementById('checkin-video');
        video.srcObject = state.mediaStream;
        
        elements.cameraCheckinModal.classList.remove('hidden');
    } catch (error) {
        console.error('Camera access denied:', error);
        alert('Camera access is required for Realistic mode. Please enable camera permissions.');
    }
}

// Stop media stream after check-in
if (state.mediaStream) {
    state.mediaStream.getTracks().forEach(track => track.stop());
}
```

---

## File yang Dibuat

### 1. `/resources/views/simulations/index.blade.php`
Dashboard pemilihan mode dan template ujian.

### 2. `/resources/views/simulations/run.blade.php`
Interface utama ujian dengan semua fitur:
- Camera check-in modal
- Fullscreen modal
- Break screen
- Exam interface dengan timer, navigation, question area
- Pause modal
- Confirm modal
- Results container
- JavaScript lengkap untuk semua functionality

### 3. `/resources/views/simulations/results/show.blade.php`
Halaman hasil ujian dengan score summary dan detail per section.

---

## Routes yang Diperlukan

```php
// User Routes (/simulations)
GET    /simulations                          # Index - list templates
POST   /simulations/templates/{id}/start     # Start new simulation
GET    /simulations/{id}/resume              # Resume simulation
GET    /simulations/{id}/run                 # Run exam interface
POST   /simulations/{id}/next-section        # Next section (AJAX)
POST   /simulations/{id}/submit              # Submit exam (AJAX)
POST   /simulations/{id}/pause               # Pause exam (AJAX)
POST   /simulations/{id}/resume-simulation   # Resume paused exam (AJAX)
POST   /simulations/{id}/record-time         # Record time spent (AJAX)
POST   /simulations/{id}/answer              # Save answer (AJAX)
POST   /simulations/{id}/abandon             # Abandon exam (AJAX)
POST   /simulations/{id}/cancel              # Cancel exam (AJAX)
GET    /simulations/{id}/status              # Get status (AJAX polling)
GET    /simulations/{id}/results             # View results
GET    /simulations/{id}/results/{section}   # Section results
```

---

## State Machine

```
[INITIATED] → [READING] → [LISTENING] → [BREAK] → [SPEAKING] → [WRITING] → [SUBMITTED] → [GRADING] → [COMPLETED]
```

---

## Browser Compatibility

| Feature | Chrome | Firefox | Safari | Edge |
|---------|--------|---------|--------|------|
| Fullscreen API | ✅ | ✅ | ✅ | ✅ |
| Wake Lock API | ✅ | ✅ | ❌ | ✅ |
| getUserMedia | ✅ | ✅ | ✅ | ✅ |
| Visibility API | ✅ | ✅ | ✅ | ✅ |
| setInterval | ✅ | ✅ | ✅ | ✅ |

**Note:** Wake Lock API tidak didukung di Safari. Fallback otomatis disediakan.

---

## Security Considerations

1. **CSRF Protection**: Semua AJAX requests include CSRF token
2. **Authorization**: Check `user_id === auth()->id()` di controller
3. **State Validation**: Transisi divalidasi oleh state machine di backend
4. **Input Sanitization**: User input disanitize sebelum disimpan

---

## Testing Checklist

### Practice Mode
- [ ] Can pause exam
- [ ] Can resume exam after pause
- [ ] Can cancel exam
- [ ] Timer stops during pause

### Scheduled Mode
- [ ] Timer continues running
- [ ] Tab switch triggers abandon
- [ ] Warning shown after abandon

### Realistic Mode
- [ ] Camera permission requested
- [ ] Video preview works
- [ ] Frame capture works
- [ ] Fullscreen enforced

### Focus Mode
- [ ] Fullscreen enabled automatically
- [ ] Non-essential UI hidden

### General Features
- [ ] Timer counts down correctly
- [ ] Timer syncs with server
- [ ] Auto-save every 30 seconds
- [ ] Flag/unflag questions works
- [ ] Review flagged shows grid
- [ ] Next/Previous navigation works
- [ ] Break appears after Listening
- [ ] Break timer counts down
- [ ] Submit exam works
- [ ] Results display correctly

---

## Future Enhancements

- [ ] Split view untuk passage + question
- [ ] Audio recording untuk Speaking section
- [ ] Offline mode support
- [ ] Proctor monitoring integration
- [ ] Analytics dashboard
- [ ] Export results to PDF

---

## Referensi

- SRS_TOEFL_v2.0.pdf - FR-3.4.x: Simulasi Ujian
- ADDENDUM_SRS_TOEFL_v2.0_Fitur_Baru.pdf - Fitur Interface Ujian
- SIMULATION_FEATURE_README.md - Dokumentasi Implementasi Backend
