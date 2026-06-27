/**
 * AI Transparency JavaScript - FR-3.5.4
 * Vanilla JS untuk highlight inline, tooltip, dan statistik
 */

(function() {
    'use strict';

    // Konfigurasi warna sesuai spesifikasi
    const HIGHLIGHT_COLORS = {
        'grammar_error': '#FF4444',
        'vocabulary_issue': '#FFBB33',
        'pronunciation_fluency': '#33B5E5',
        'organization_issue': '#FF8800'
    };

    const HIGHLIGHT_LABELS = {
        'grammar_error': 'Grammar Error',
        'vocabulary_issue': 'Vocabulary Issue',
        'pronunciation_fluency': 'Pronunciation/Fluency',
        'organization_issue': 'Organization/Development'
    };

    // State management
    let state = {
        annotationsVisible: true,
        currentTooltip: null,
        highlights: [],
        statistics: {}
    };

    /**
     * Inisialisasi komponen AI Transparency
     * @param {Object} data - Data dari AI grading results
     * @param {string} data.content - Teks atau transkrip
     * @param {Array} data.highlights - Array highlight dari AI
     * @param {number} data.confidence_score - Skor confidence 0-100
     * @param {string} data.type - 'speaking' atau 'writing'
     * @param {string} data.audio_url - URL audio (opsional, untuk speaking)
     */
    function init(data) {
        if (!data || !data.content) {
            console.error('AI Transparency: Data tidak valid');
            return;
        }

        state.highlights = data.highlights || [];
        state.statistics = calculateStatistics(data.content);

        renderHighlights(data.content, data.type);
        renderStatistics(state.statistics);
        updateConfidenceScore(data.confidence_score || 0);
        setupEventListeners(data.type);

        if (data.type === 'speaking' && data.audio_url) {
            setupAudioPlayer(data.audio_url);
        }
    }

    /**
     * Render highlights pada teks
     * @param {string} content - Teks asli
     * @param {string} type - Tipe konten
     */
    function renderHighlights(content, type) {
        const container = document.getElementById('highlightedContent');
        if (!container) return;

        // Sort highlights by position (start_index)
        const sortedHighlights = [...state.highlights].sort((a, b) => 
            (a.position?.start || 0) - (b.position?.start || 0)
        );

        let result = '';
        let lastIndex = 0;

        sortedHighlights.forEach((highlight, index) => {
            const start = highlight.position?.start || 0;
            const end = highlight.position?.end || 0;

            // Tambahkan teks sebelum highlight
            if (start > lastIndex) {
                result += escapeHtml(content.substring(lastIndex, start));
            }

            // Tambahkan highlight span
            const highlightedText = escapeHtml(content.substring(start, end));
            const errorType = highlight.type || 'grammar_error';
            const cssClass = errorType.replace('_', '-');
            
            result += `<span 
                class="highlight ${cssClass}" 
                data-highlight-index="${index}"
                data-type="${errorType}"
                tabindex="0"
                role="mark"
                aria-label="${HIGHLIGHT_LABELS[errorType] || 'Issue'}: ${highlight.message || ''}"
            >${highlightedText}</span>`;

            lastIndex = end;
        });

        // Tambahkan sisa teks
        if (lastIndex < content.length) {
            result += escapeHtml(content.substring(lastIndex));
        }

        container.innerHTML = result;

        // Sembunyikan marker pronunciation untuk writing
        if (type === 'writing') {
            document.querySelectorAll('.legend-item.speaking-only').forEach(el => {
                el.style.display = 'none';
            });
        }
    }

    /**
     * Setup event listeners untuk interaksi
     * @param {string} type - Tipe konten
     */
    function setupEventListeners(type) {
        const container = document.getElementById('highlightedContent');
        const tooltip = document.getElementById('tooltip');
        const toggleCheckbox = document.getElementById('toggleAnnotations');
        const toggleLabel = document.getElementById('toggleLabel');

        // Toggle annotations
        if (toggleCheckbox) {
            toggleCheckbox.addEventListener('change', function() {
                state.annotationsVisible = this.checked;
                container.classList.toggle('annotations-hidden', !this.checked);
                toggleLabel.textContent = this.checked ? 'Tampilkan Anotasi' : 'Sembunyikan Anotasi';
                
                // Update ARIA
                this.setAttribute('aria-checked', this.checked);
            });
        }

        // Hover dan focus events untuk highlights
        container.addEventListener('mouseover', handleMouseOver);
        container.addEventListener('mouseout', handleMouseOut);
        container.addEventListener('focusin', handleFocusIn);
        container.addEventListener('focusout', handleFocusOut);
        
        // Keyboard navigation
        container.addEventListener('keydown', handleKeyDown);

        // Tooltip close button
        if (tooltip) {
            const closeBtn = tooltip.querySelector('.tooltip-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', hideTooltip);
            }

            // Close tooltip saat klik di luar
            document.addEventListener('click', function(e) {
                if (!tooltip.contains(e.target) && !e.target.classList.contains('highlight')) {
                    hideTooltip();
                }
            });
        }

        // Resize handler untuk reposition tooltip
        window.addEventListener('resize', function() {
            if (state.currentTooltip !== null) {
                const highlightEl = container.querySelector(`[data-highlight-index="${state.currentTooltip}"]`);
                if (highlightEl) {
                    showTooltip(highlightEl, state.currentTooltip);
                }
            }
        });
    }

    /**
     * Handle mouse over pada highlight
     */
    function handleMouseOver(e) {
        if (!e.target.classList.contains('highlight')) return;
        if (!state.annotationsVisible) return;

        const index = e.target.getAttribute('data-highlight-index');
        if (index !== null) {
            showTooltip(e.target, parseInt(index));
        }
    }

    /**
     * Handle mouse out pada highlight
     */
    function handleMouseOut(e) {
        if (!e.target.classList.contains('highlight')) return;
        
        // Delay hide untuk memungkinkan klik/tap
        setTimeout(() => {
            if (!e.target.matches(':hover') && !e.target.matches(':focus')) {
                hideTooltip();
            }
        }, 100);
    }

    /**
     * Handle focus in untuk accessibility
     */
    function handleFocusIn(e) {
        if (!e.target.classList.contains('highlight')) return;

        const index = e.target.getAttribute('data-highlight-index');
        if (index !== null) {
            showTooltip(e.target, parseInt(index));
        }
    }

    /**
     * Handle focus out untuk accessibility
     */
    function handleFocusOut(e) {
        if (!e.target.classList.contains('highlight')) return;
        
        setTimeout(() => {
            const activeElement = document.activeElement;
            if (!activeElement.classList.contains('highlight')) {
                hideTooltip();
            }
        }, 100);
    }

    /**
     * Handle keyboard navigation
     */
    function handleKeyDown(e) {
        if (!e.target.classList.contains('highlight')) return;

        switch(e.key) {
            case 'Enter':
            case ' ':
                e.preventDefault();
                const index = e.target.getAttribute('data-highlight-index');
                if (index !== null) {
                    showTooltip(e.target, parseInt(index));
                }
                break;
            case 'Escape':
                hideTooltip();
                e.target.blur();
                break;
            case 'ArrowRight':
            case 'ArrowDown':
                e.preventDefault();
                focusNextHighlight(e.target);
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                e.preventDefault();
                focusPreviousHighlight(e.target);
                break;
        }
    }

    /**
     * Fokus ke highlight berikutnya
     */
    function focusNextHighlight(current) {
        const container = document.getElementById('highlightedContent');
        const highlights = container.querySelectorAll('.highlight');
        const currentIndex = Array.from(highlights).indexOf(current);
        
        if (currentIndex < highlights.length - 1) {
            highlights[currentIndex + 1].focus();
        } else {
            highlights[0].focus(); // Loop ke awal
        }
    }

    /**
     * Fokus ke highlight sebelumnya
     */
    function focusPreviousHighlight(current) {
        const container = document.getElementById('highlightedContent');
        const highlights = container.querySelectorAll('.highlight');
        const currentIndex = Array.from(highlights).indexOf(current);
        
        if (currentIndex > 0) {
            highlights[currentIndex - 1].focus();
        } else {
            highlights[highlights.length - 1].focus(); // Loop ke akhir
        }
    }

    /**
     * Tampilkan tooltip dengan informasi highlight
     * @param {HTMLElement} element - Element highlight
     * @param {number} index - Index highlight
     */
    function showTooltip(element, index) {
        const tooltip = document.getElementById('tooltip');
        if (!tooltip || !state.highlights[index]) return;

        const highlight = state.highlights[index];
        const errorType = highlight.type || 'grammar_error';
        const cssClass = errorType.replace('_', '-');

        // Update tooltip content
        const typeEl = tooltip.querySelector('.tooltip-type');
        if (typeEl) {
            typeEl.textContent = HIGHLIGHT_LABELS[errorType] || 'Issue';
            typeEl.className = `tooltip-type ${cssClass}`;
        }

        const explanationEl = tooltip.querySelector('.tooltip-explanation');
        if (explanationEl) {
            explanationEl.textContent = highlight.message || 'Tidak ada penjelasan.';
        }

        const suggestionEl = tooltip.querySelector('.tooltip-suggestion-text');
        if (suggestionEl) {
            suggestionEl.textContent = highlight.suggestion || 'Tidak ada saran.';
        }

        const exampleEl = tooltip.querySelector('.tooltip-example-text');
        if (exampleEl) {
            exampleEl.textContent = highlight.example || 'Tidak ada contoh.';
        }

        // Position tooltip
        positionTooltip(tooltip, element);

        // Show tooltip
        tooltip.classList.add('visible');
        tooltip.setAttribute('aria-hidden', 'false');
        state.currentTooltip = index;

        // Announce to screen readers
        announceToScreenReader(`${HIGHLIGHT_LABELS[errorType]}. ${highlight.message || ''}`);
    }

    /**
     * Position tooltip di dekat element
     */
    function positionTooltip(tooltip, element) {
        const rect = element.getBoundingClientRect();
        const tooltipRect = tooltip.getBoundingClientRect();
        
        let top = rect.bottom + window.scrollY + 10;
        let left = rect.left + window.scrollX;

        // Adjust jika tooltip overflow kanan
        if (left + tooltipRect.width > window.innerWidth - 20) {
            left = window.innerWidth - tooltipRect.width - 20;
        }

        // Adjust jika tooltip overflow bawah, tampilkan di atas
        if (top + tooltipRect.height > window.innerHeight + window.scrollY - 20) {
            top = rect.top + window.scrollY - tooltipRect.height - 10;
        }

        // Pastikan tidak overflow kiri
        if (left < 10) {
            left = 10;
        }

        tooltip.style.top = `${top}px`;
        tooltip.style.left = `${left}px`;
    }

    /**
     * Sembunyikan tooltip
     */
    function hideTooltip() {
        const tooltip = document.getElementById('tooltip');
        if (tooltip) {
            tooltip.classList.remove('visible');
            tooltip.setAttribute('aria-hidden', 'true');
            state.currentTooltip = null;
        }
    }

    /**
     * Setup audio player untuk speaking
     * @param {string} audioUrl - URL file audio
     */
    function setupAudioPlayer(audioUrl) {
        const audioPlayer = document.getElementById('audioPlayer');
        const audioElement = document.getElementById('audioElement');
        
        if (!audioPlayer || !audioElement) return;

        audioPlayer.style.display = 'block';
        audioElement.src = audioUrl;

        // Render audio highlights (time-based markers)
        const audioHighlightsContainer = document.getElementById('audioHighlights');
        if (audioHighlightsContainer && state.highlights.length > 0) {
            state.highlights.forEach((highlight, index) => {
                if (highlight.timestamp) {
                    const marker = document.createElement('div');
                    const errorType = highlight.type || 'grammar_error';
                    const cssClass = errorType.replace('_', '-');
                    
                    marker.className = `audio-highlight-marker ${cssClass}`;
                    marker.textContent = formatTime(highlight.timestamp);
                    marker.setAttribute('tabindex', '0');
                    marker.setAttribute('role', 'button');
                    marker.setAttribute('aria-label', `Loncat ke ${formatTime(highlight.timestamp)} - ${HIGHLIGHT_LABELS[errorType]}`);
                    
                    marker.addEventListener('click', function() {
                        audioElement.currentTime = highlight.timestamp;
                        audioElement.play();
                    });

                    marker.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            audioElement.currentTime = highlight.timestamp;
                            audioElement.play();
                        }
                    });

                    audioHighlightsContainer.appendChild(marker);
                }
            });
        }
    }

    /**
     * Hitung statistik teks
     * @param {string} text - Teks untuk dianalisis
     * @returns {Object} Statistik
     */
    function calculateStatistics(text) {
        // Word count
        const words = text.trim().split(/\s+/).filter(word => word.length > 0);
        const wordCount = words.length;

        // Sentence count
        const sentences = text.split(/[.!?]+/).filter(s => s.trim().length > 0);
        const sentenceCount = sentences.length;

        // Average sentence length
        const avgSentenceLength = sentenceCount > 0 
            ? Math.round((wordCount / sentenceCount) * 10) / 10 
            : 0;

        // Unique words count (case-insensitive)
        const uniqueWords = new Set(words.map(word => word.toLowerCase().replace(/[^a-z0-9]/gi, '')));
        const uniqueWordsCount = uniqueWords.size;

        return {
            wordCount,
            sentenceCount,
            avgSentenceLength,
            uniqueWordsCount
        };
    }

    /**
     * Render statistik ke UI
     * @param {Object} stats - Objek statistik
     */
    function renderStatistics(stats) {
        const wordCountEl = document.getElementById('wordCount');
        const sentenceCountEl = document.getElementById('sentenceCount');
        const avgSentenceLengthEl = document.getElementById('avgSentenceLength');
        const uniqueWordsCountEl = document.getElementById('uniqueWordsCount');

        if (wordCountEl) wordCountEl.textContent = stats.wordCount || 0;
        if (sentenceCountEl) sentenceCountEl.textContent = stats.sentenceCount || 0;
        if (avgSentenceLengthEl) avgSentenceLengthEl.textContent = stats.avgSentenceLength || 0;
        if (uniqueWordsCountEl) uniqueWordsCountEl.textContent = stats.uniqueWordsCount || 0;
    }

    /**
     * Update confidence score circular chart
     * @param {number} score - Skor 0-100
     */
    function updateConfidenceScore(score) {
        const confidenceContainer = document.querySelector('.confidence-score');
        const circle = document.querySelector('.circle');
        const percentageText = document.querySelector('.percentage');

        if (!confidenceContainer || !circle || !percentageText) return;

        // Clamp score between 0 and 100
        const clampedScore = Math.max(0, Math.min(100, score));

        // Update SVG stroke-dasharray
        circle.setAttribute('stroke-dasharray', `${clampedScore}, 100`);
        
        // Update text
        percentageText.textContent = `${Math.round(clampedScore)}%`;

        // Update ARIA
        confidenceContainer.setAttribute('aria-valuenow', Math.round(clampedScore));

        // Update color based on score
        circle.style.stroke = getConfidenceColor(clampedScore);
    }

    /**
     * Dapatkan warna confidence berdasarkan skor
     */
    function getConfidenceColor(score) {
        if (score >= 80) return '#4CAF50'; // Green
        if (score >= 60) return '#FF9800'; // Orange
        return '#F44336'; // Red
    }

    /**
     * Escape HTML untuk mencegah XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Format waktu dalam detik ke MM:SS
     */
    function formatTime(seconds) {
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
    }

    /**
     * Announce message ke screen reader
     */
    function announceToScreenReader(message) {
        const announcer = document.createElement('div');
        announcer.setAttribute('role', 'status');
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        announcer.className = 'sr-only';
        announcer.textContent = message;
        
        document.body.appendChild(announcer);
        
        setTimeout(() => {
            document.body.removeChild(announcer);
        }, 1000);
    }

    // Expose init function to global scope
    window.AITransparency = {
        init: init,
        hideTooltip: hideTooltip,
        calculateStatistics: calculateStatistics
    };

})();
