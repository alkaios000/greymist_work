/**
 * File: assets/JS/program_function/ai/ai-voice-engine.js
 * Modul: Fitur "Sound to Design" (Speech-to-Text & Instant AI Design Generation).
 *        Mendukung Web Speech API (Chrome/Edge SpeechRecognition) dan Fallback Groq Whisper Audio MediaRecorder.
 */
(function (window) {
    "use strict";

    window.GreymistAI = window.GreymistAI || {};

    let recognitionInstance = null;
    let isRecordingVoice = false;
    let mediaRecorderInstance = null;
    let audioChunks = [];

    function setupVoiceEngine(options = {}) {
        const voiceBarBtn = options.voiceBarBtn || document.getElementById("gm-ai-voice-bar-btn");
        const promptMicBtn = options.promptMicBtn || document.getElementById("gm-ai-prompt-mic-btn");
        const promptInputEl = options.promptInput || document.getElementById("gm-ai-prompt-input");
        const statusEl = options.statusEl || document.getElementById("gm-ai-status-msg");
        const generateBtn = options.generateBtn || document.getElementById("gm-ai-generate-btn");
        function setVoiceListeningUI(listening, customStatus = "") {
            isRecordingVoice = listening;
            const micBtns = [voiceBarBtn, promptMicBtn].filter(Boolean);
            micBtns.forEach(btn => {
                if (listening) {
                    btn.classList.add("gm-ai-listening");
                    const icon = btn.querySelector("i");
                    if (icon) icon.className = "fa-solid fa-microphone-lines";
                } else {
                    btn.classList.remove("gm-ai-listening");
                    const icon = btn.querySelector("i");
                    if (icon) icon.className = "fa-solid fa-microphone";
                }
            });

            if (listening) {
                if (promptInputEl) {
                    if (!promptInputEl.hasAttribute("data-prev-ph")) {
                        promptInputEl.setAttribute("data-prev-ph", promptInputEl.placeholder);
                    }
                    promptInputEl.placeholder = "🎙️ Mendengarkan suara Anda... (Bicara sekarang)";
                }
                showStatus(statusEl, "info", customStatus || "🎙️ <strong>Mendengarkan suara...</strong> Katakan instruksi desain yang Anda inginkan (misal: <em>\"Buatkan hero section modern toko bunga\"</em>).");
            } else {
                if (promptInputEl && promptInputEl.hasAttribute("data-prev-ph")) {
                    promptInputEl.placeholder = promptInputEl.getAttribute("data-prev-ph");
                }
            }
        }

        async function triggerSoundToDesignAutoRun(transcriptText) {
            if (!transcriptText || transcriptText.trim() === "") {
                showStatus(statusEl, "info", "🎙️ Tidak ada suara atau kata yang tertangkap. Silakan klik icon mic lagi untuk mencoba.");
                return;
            }
            const cleanText = transcriptText.trim();
            if (promptInputEl) {
                promptInputEl.value = cleanText;
            }
            const safeText = cleanText.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
            showStatus(statusEl, "info", `🎙️ <strong>Suara Terdeteksi:</strong> "${safeText}"<br><span style="color:#0284c7;font-weight:600;"><i class="fas fa-spinner fa-spin"></i> Langsung memproses pembuatan desain...</span>`);

            // Jeda 400ms agar teks tampil di layar, lalu otomatis picu tombol Jalankan AI
            setTimeout(() => {
                const genBtn = document.getElementById("gm-ai-generate-btn");
                if (genBtn && !genBtn.disabled) {
                    genBtn.click();
                }
            }, 400);
        }

        function toggleSoundToDesign() {
            if (isRecordingVoice) {
                // User klik lagi saat merekam -> stop dan proses hasil
                if (recognitionInstance) {
                    try { recognitionInstance.stop(); } catch (_) {}
                }
                if (mediaRecorderInstance && mediaRecorderInstance.state !== "inactive") {
                    try { mediaRecorderInstance.stop(); } catch (_) {}
                }
                setVoiceListeningUI(false);
                return;
            }

            const SpeechRec = window.SpeechRecognition || window.webkitSpeechRecognition;

            if (SpeechRec) {
                try {
                    const recognition = new SpeechRec();
                    recognitionInstance = recognition;
                    recognition.lang = "id-ID";
                    recognition.interimResults = true;
                    recognition.continuous = false;
                    recognition.maxAlternatives = 1;

                    let finalTranscript = "";

                    recognition.onstart = () => {
                        setVoiceListeningUI(true);
                    };

                    recognition.onresult = (e) => {
                        let interim = "";
                        for (let i = e.resultIndex; i < e.results.length; ++i) {
                            if (e.results[i].isFinal) {
                                finalTranscript += e.results[i][0].transcript;
                            } else {
                                interim += e.results[i][0].transcript;
                            }
                        }
                        const currentSpoken = finalTranscript || interim;
                        if (currentSpoken && promptInputEl) {
                            promptInputEl.value = currentSpoken.trim();
                        }
                    };

                    recognition.onerror = (e) => {
                        setVoiceListeningUI(false);
                        recognitionInstance = null;
                        if (e.error === "no-speech") {
                            showStatus(statusEl, "info", "🎙️ Tidak ada suara yang terdeteksi. Silakan klik mic lagi.");
                        } else if (e.error === "not-allowed") {
                            showStatus(statusEl, "error", "⚠️ Akses mikrofon diblokir oleh browser. Mohon izinkan mikrofon di address bar browser Anda.");
                        } else {
                            showStatus(statusEl, "error", `⚠️ Masalah mikrofon (${e.error}). Silakan coba lagi.`);
                        }
                    };

                    recognition.onend = () => {
                        setVoiceListeningUI(false);
                        recognitionInstance = null;
                        const captured = (promptInputEl ? promptInputEl.value : "") || finalTranscript;
                        if (captured && captured.trim() !== "") {
                            triggerSoundToDesignAutoRun(captured.trim());
                        }
                    };

                    recognition.start();
                } catch (err) {
                    console.warn("[Sound to Design] Web Speech API error, beralih ke perekaman Whisper:", err);
                    startMediaRecorderFallback();
                }
            } else {
                startMediaRecorderFallback();
            }
        }

        async function startMediaRecorderFallback() {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || typeof MediaRecorder === "undefined") {
                showStatus(statusEl, "error", "⚠️ Browser ini belum mendukung Web Speech API atau MediaRecorder. Disarankan menggunakan Google Chrome atau Microsoft Edge.");
                return;
            }
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                audioChunks = [];
                const mediaRecorder = new MediaRecorder(stream);
                mediaRecorderInstance = mediaRecorder;

                mediaRecorder.ondataavailable = (e) => {
                    if (e.data && e.data.size > 0) audioChunks.push(e.data);
                };

                mediaRecorder.onstart = () => {
                    setVoiceListeningUI(true, "🎙️ <strong>Merekam suara...</strong> Bicaralah sekarang, lalu klik icon mic lagi untuk selesai & langsung mendesain.");
                };

                mediaRecorder.onstop = async () => {
                    setVoiceListeningUI(false);
                    stream.getTracks().forEach(t => t.stop());
                    if (audioChunks.length === 0) return;

                    const audioBlob = new Blob(audioChunks, { type: "audio/webm" });
                    showStatus(statusEl, "info", "🎙️ <i class=\"fas fa-spinner fa-spin\"></i> Mengonversi rekaman suara menjadi teks via AI...");

                    try {
                        const formData = new FormData();
                        formData.append("audio", audioBlob, "voice.webm");
                        const resp = await fetch("index.php?page=ai_action&ai_action=ai_transcribe_audio", {
                            method: "POST",
                            body: formData
                        });
                        const resJson = await resp.json();
                        if (resJson.success && resJson.transcript) {
                            triggerSoundToDesignAutoRun(resJson.transcript);
                        } else {
                            showStatus(statusEl, "error", resJson.message || "Gagal mentranskripsi suara.");
                        }
                    } catch (e) {
                        showStatus(statusEl, "error", "Gagal menghubungi server untuk transkripsi suara.");
                    }
                };

                mediaRecorder.start();
            } catch (err) {
                setVoiceListeningUI(false);
                showStatus(statusEl, "error", "⚠️ Izin mikrofon ditolak atau perangkat mikrofon tidak aktif.");
            }
        }

        if (voiceBarBtn) {
            voiceBarBtn.addEventListener("click", toggleSoundToDesign);
        }
        if (promptMicBtn) {
            promptMicBtn.addEventListener("click", toggleSoundToDesign);
        }



        return {
            setVoiceListeningUI,
            triggerSoundToDesignAutoRun,
            toggleSoundToDesign,
            startMediaRecorderFallback
        };
    }

    window.GreymistAI.VoiceEngine = {
        setup: setupVoiceEngine
    };
    window.setupVoiceEngine = setupVoiceEngine;

})(window);
