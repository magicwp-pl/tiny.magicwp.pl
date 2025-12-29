(function() {
    if (typeof wp === 'undefined' || !wp.plugins) {
        return;
    }

    const { registerPlugin } = wp.plugins;
    const { useSelect } = wp.data;
    const { CheckboxControl } = wp.components;
    const { __ } = wp.i18n;
    const { useEffect, useState, createElement } = wp.element;
    const { apiFetch } = wp;

    let PanelComponent = null;
    if (wp.editor && wp.editor.PluginDocumentSettingPanel) {
        PanelComponent = wp.editor.PluginDocumentSettingPanel;
    } else if (wp.editPost && wp.editPost.PluginDocumentSettingPanel) {
        PanelComponent = wp.editPost.PluginDocumentSettingPanel;
    } else if (wp.editSite && wp.editSite.PluginDocumentSettingPanel) {
        PanelComponent = wp.editSite.PluginDocumentSettingPanel;
    }

    if (!PanelComponent) {
        return;
    }

    function TailwindPagePanel() {
        const { postId, postType } = useSelect((select) => {
            const editor = select('core/editor');
            
            if (editor && editor.getCurrentPostId) {
                return {
                    postId: editor.getCurrentPostId(),
                    postType: editor.getCurrentPostType()
                };
            }
            
            return { postId: null, postType: null };
        }, []);

        const [useTailwind, setUseTailwind] = useState(false);
        const [isLoading, setIsLoading] = useState(true);

        const templateId = useSelect((select) => {
            const editor = select('core/editor');
            if (editor && editor.getCurrentPostId) {
                return editor.getCurrentPostId();
            }
            return postId;
        }, [postId]);

        useEffect(() => {
            if (postType !== 'wp_template' && postType !== 'wp_template_part') {
                setIsLoading(false);
                return;
            }

            if (!templateId) {
                setIsLoading(false);
                return;
            }

            let apiPath = '/wp/v2/templates/' + templateId;
            if (typeof templateId === 'string' && templateId.includes('//')) {
                apiPath = '/wp/v2/templates/' + encodeURIComponent(templateId);
            }

            apiFetch({
                path: apiPath + '?context=edit'
            }).then((template) => {
                const useTailwindValue = template.use_tailwind === true || template.use_tailwind === '1' || template.use_tailwind === 1;
                setUseTailwind(useTailwindValue);
                setIsLoading(false);
            }).catch((error) => {
                console.error('Tailwind Page: Error loading template:', error);
                setIsLoading(false);
            });
        }, [templateId, postType]);

        const handleChange = (checked) => {
            if (!templateId) {
                return;
            }

            setUseTailwind(checked);

            let apiPath = '/wp/v2/templates/' + templateId;
            if (typeof templateId === 'string' && templateId.includes('//')) {
                apiPath = '/wp/v2/templates/' + encodeURIComponent(templateId);
            }

            apiFetch({
                path: apiPath,
                method: 'POST',
                data: {
                    use_tailwind: checked
                }
            }).catch((error) => {
                console.error('Tailwind Page: Error saving', error);
                setUseTailwind(!checked);
            });
        };

        if (postType !== 'wp_template' && postType !== 'wp_template_part') {
            return null;
        }

        if (isLoading) {
            return createElement(
                PanelComponent,
                {
                    name: 'tailwind-page-panel',
                    title: __('Tailwind CSS', 'tailwind-page')
                },
                createElement('p', null, __('Ładowanie...', 'tailwind-page'))
            );
        }

        return createElement(
            PanelComponent,
            {
                name: 'tailwind-page-panel',
                title: __('Tailwind CSS', 'tailwind-page')
            },
            createElement(CheckboxControl, {
                label: __('Użyj Tailwind CSS', 'tailwind-page'),
                checked: useTailwind,
                onChange: handleChange,
                __nextHasNoMarginBottom: true
            }),
            createElement('p', {
                style: { fontSize: '12px', color: '#666', marginTop: '8px' }
            }, __('Włącza Tailwind CSS i usuwa domyślne style WordPress dla tego template.', 'tailwind-page'))
        );
    }

    registerPlugin('tailwind-page-template-panel', {
        render: TailwindPagePanel,
        icon: null
    });
})();
