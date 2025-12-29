(function () {
    const { registerBlockType } = wp.blocks;
    const { createElement: el, Fragment } = wp.element;
    const { TextareaControl, ToolbarGroup, ToolbarButton } = wp.components;
    const { useBlockProps, BlockControls } = wp.blockEditor;
    const { __ } = wp.i18n;
    const React = wp.element;
    registerBlockType('tailwind-page/html-tailwind', {
        title: 'Tailwind HTML',
        icon: { src: 'editor-code' },
        category: 'widgets',
        attributes: {
            content: {
                type: 'string',
                source: 'html',
                selector: 'div',
            },
        },
        edit: function (props) {
            const { attributes, setAttributes } = props;
            const { content } = attributes;

            const blockProps = useBlockProps();
            const previewRef = React.useRef(null);
            const [localHtml, setLocalHtml] = React.useState(content || '');
            const [showPreview, setShowPreview] = React.useState(content ? true : false);

            // jeśli atrybut zmieni się z zewnątrz (undo/redo), zsynchronizuj
            React.useEffect(() => {
                if (content !== localHtml) setLocalHtml(content || '');
            }, [content]);

            // Obsługa kliknięć na div.img-placeholder w trybie preview (event delegation)
            React.useEffect(() => {
                if (!showPreview || !previewRef.current) return;

                const previewElement = previewRef.current;

                const handleClick = (e) => {
                    let target = e.target;
                    while (target && target !== previewElement) {
                        if (target.tagName === 'DIV' && target.classList.contains('img-placeholder')) {
                            e.preventDefault();
                            e.stopPropagation();

                            if (window.wp && window.wp.media) {
                                const frame = window.wp.media({
                                    title: __('Select Image', 'tailwind-page'),
                                    button: { text: __('Use this image', 'tailwind-page') },
                                    multiple: false,
                                    library: { type: 'image' }
                                });

                                frame.on('select', function () {
                                    const attachment = frame.state().get('selection').first().toJSON();
                                    
                                    const existingImg = target.querySelector('img');
                                    const preservedClass = existingImg ? existingImg.getAttribute('class') : null;
                                    const preservedStyle = existingImg ? existingImg.getAttribute('style') : null;
                                    
                                    target.innerHTML = '';

                                    const img = document.createElement('img');
                                    img.setAttribute('src', attachment.url);
                                    if (attachment.alt) {
                                        img.setAttribute('alt', attachment.alt);
                                    }
                                    if (preservedClass) {
                                        img.setAttribute('class', preservedClass);
                                    }
                                    if (preservedStyle) {
                                        img.setAttribute('style', preservedStyle);
                                    }
                                    target.appendChild(img);

                                    const cleanedHtml = previewElement.innerHTML;
                                    setLocalHtml(cleanedHtml);
                                    setAttributes({ content: cleanedHtml });
                                });

                                frame.open();
                            }
                            return;
                        }
                        target = target.parentElement;
                    }
                };

                // Dodaj event listener na poziomie parent (event delegation)
                previewElement.addEventListener('click', handleClick);

                // Cleanup
                return () => {
                    previewElement.removeEventListener('click', handleClick);
                };
            }, [showPreview, content, setAttributes]);

            const onInput = (e) => {
                setLocalHtml(e.currentTarget.innerHTML); // brak setAttributes -> caret nie skacze
            };
            const onBlur = () => {
                if (localHtml !== content) {
                    setAttributes({ content: localHtml });
                    setLocalHtml(localHtml);
                }
            };

            return el(Fragment, {},
                // Dodaj style CSS dla placeholderów
                el('style', {}, `
                    div.img-placeholder {
                        cursor: pointer !important;
                        opacity: 0.7 !important;
                    }
                    div.img-placeholder:empty {
                        border: 2px dashed #ccc;
                        border-radius: 4px;
                    }
                `),
                el(BlockControls, {},
                    el(ToolbarGroup, {},
                        el(ToolbarButton, {
                            text: __('HTML', 'tailwind-page'),
                            label: __('HTML', 'tailwind-page'),
                            isPressed: !showPreview,
                            onClick: () => setShowPreview(false)
                        }),
                        el(ToolbarButton, {
                            text: __('Preview', 'tailwind-page'),
                            label: __('Preview', 'tailwind-page'),
                            isPressed: showPreview,
                            onClick: () => setShowPreview(true)
                        })
                    )
                ),
                el('div', blockProps,
                    el('div', {},
                        // Content
                        showPreview ?
                            // Podgląd
                            el('div', {
                                ref: previewRef,
                                contentEditable: true,
                                onInput,
                                onBlur,
                                style: {
                                    fontFamily: 'ui-sans-serif, system-ui, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji'
                                },
                                dangerouslySetInnerHTML: { __html: content || '' }
                            }) :
                            // Kod
                            el(TextareaControl, {
                                value: content,
                                onChange: (value) => setAttributes({ content: value }),
                                placeholder: __('Wklej tutaj HTML z klasami Tailwind CSS...', 'tailwind-page'),
                                style: {
                                    fontFamily: 'monospace',

                                    minHeight: '70vh'
                                }
                            })
                    )
                )
            );
        },
        save: ({ attributes }) => {
            return wp.element.createElement(wp.element.RawHTML, {
                children: attributes.content || ''
            });
        }
    });
})();
