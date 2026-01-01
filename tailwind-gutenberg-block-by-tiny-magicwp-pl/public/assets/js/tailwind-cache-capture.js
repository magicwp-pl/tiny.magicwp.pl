(function() {
    'use strict';

    if (typeof tailwindCacheCapture === 'undefined') {
        return;
    }

    const config = tailwindCacheCapture;
    let capturedStyles = [];
    let hasTailwindScript = false;
    let observer = null;
    let captureTimeout = null;
    let isCapturing = false;

    function checkForTailwindScript() {
        const scripts = document.querySelectorAll('script[src*="tailwindcss"]');
        if (scripts.length > 0) {
            hasTailwindScript = true;
        }
    }

    function isTailwindStyle(style) {
        const textContent = style.textContent || '';
        const id = style.getAttribute('id') || '';
        const sourceURL = textContent.match(/sourceURL=([^\s]+)/);
        
        if (sourceURL && (sourceURL[1].indexOf('admin-bar') !== -1 || 
                         sourceURL[1].indexOf('wp-') !== -1 ||
                         sourceURL[1].indexOf('wordpress') !== -1)) {
            return false;
        }
        
        if (id.indexOf('admin-bar') !== -1 || 
            id.indexOf('wp-') !== -1 ||
            id.indexOf('dashicons') !== -1) {
            return false;
        }
        
        const hasTailwindMarkers = textContent.indexOf('--tw-') !== -1 || 
                                  textContent.indexOf('tailwindcss') !== -1 ||
                                  textContent.indexOf('! tailwindcss') !== -1;
        
        return hasTailwindMarkers;
    }

    function collectStyles() {
        if (isCapturing) {
            return;
        }

        if (!hasTailwindScript) {
            checkForTailwindScript();
        }

        if (!hasTailwindScript) {
            return;
        }

        const styles = document.querySelectorAll('style');
        capturedStyles = [];

        styles.forEach(function(style) {
            if (style.textContent && style.textContent.trim().length > 0) {
                if (isTailwindStyle(style)) {
                    capturedStyles.push(style.textContent);
                }
            }
        });

        if (capturedStyles.length > 0) {
            sendStylesToBackend();
        }
    }

    function sendStylesToBackend() {
        if (isCapturing) {
            return;
        }

        isCapturing = true;

        const cssContent = capturedStyles.join('\n\n');
        const currentPath = window.location.pathname;

        const formData = new FormData();
        formData.append('action', 'tailwind_save_cache');
        formData.append('nonce', config.nonce);
        formData.append('url', currentPath);
        formData.append('css', cssContent);

        fetch(config.ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            if (data.success) {
                console.log('Tailwind cache saved successfully');
            }
        })
        .catch(function(error) {
            console.error('Error saving Tailwind cache:', error);
        })
        .finally(function() {
            isCapturing = false;
        });
    }

    function startCapture() {
        checkForTailwindScript();

        if (observer) {
            observer.disconnect();
        }

        observer = new MutationObserver(function(mutations) {
            let shouldCollect = false;

            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    mutation.addedNodes.forEach(function(node) {
                        if (node.nodeType === 1) {
                            if (node.tagName === 'STYLE') {
                                shouldCollect = true;
                            }
                            if (node.tagName === 'SCRIPT' && node.src && node.src.indexOf('tailwindcss') !== -1) {
                                hasTailwindScript = true;
                                shouldCollect = true;
                            }
                        }
                    });
                }
            });

            if (shouldCollect) {
                if (captureTimeout) {
                    clearTimeout(captureTimeout);
                }
                captureTimeout = setTimeout(function() {
                    collectStyles();
                }, 1000);
            }
        });

        observer.observe(document.head, {
            childList: true,
            subtree: true
        });

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(function() {
                    collectStyles();
                }, 2000);
            });
        } else {
            setTimeout(function() {
                collectStyles();
            }, 2000);
        }

        if (typeof requestIdleCallback !== 'undefined') {
            requestIdleCallback(function() {
                collectStyles();
            }, { timeout: 5000 });
        } else {
            setTimeout(function() {
                collectStyles();
            }, 5000);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startCapture);
    } else {
        startCapture();
    }
})();

