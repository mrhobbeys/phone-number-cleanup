(function(){
    const { __ } = wp.i18n;
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls } = wp.blockEditor || wp.editor;
    const { PanelBody, ToggleControl } = wp.components;

    registerBlockType('phone-number-clean-up/form', {
        edit: (props) => {
            const { attributes, setAttributes } = props;
            const blockProps = useBlockProps();
            return (
                wp.element.createElement('div', blockProps,
                    wp.element.createElement(InspectorControls, {},
                        wp.element.createElement(PanelBody, { title: __('Settings', 'phone-number-clean-up'), initialOpen: true },
                            wp.element.createElement(ToggleControl, {
                                label: __('Show normalized numbers', 'phone-number-clean-up'),
                                checked: attributes.showNormalized,
                                onChange: (val) => setAttributes({ showNormalized: val })
                            })
                        )
                    ),
                    wp.element.createElement('p', {}, __('Phone Number Clean Up form will render on the front end.', 'phone-number-clean-up'))
                )
            );
        }
    });
})();
