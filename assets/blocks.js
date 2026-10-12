/* Editor side of the Stamp.Show blocks: no build step, plain wp.* globals. Rendering is server-side. */
(function (wp, config) {
    var el = wp.element.createElement;
    var Fragment = wp.element.Fragment;
    var ServerSideRender = wp.serverSideRender;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var PanelBody = wp.components.PanelBody;
    var TextControl = wp.components.TextControl;
    var RangeControl = wp.components.RangeControl;

    var labels = {
        category: 'Only this sponsor category (optional)',
        classes: 'Exhibit classes, comma separated (empty = free text)',
        max_frames: 'Maximum frames'
    };

    Object.keys(config.blocks).forEach(function (slug) {
        var def = config.blocks[slug];
        var name = 'stamp-show/' + slug;

        wp.blocks.registerBlockType(name, {
            apiVersion: 2,
            title: 'Stamp.Show: ' + def.title,
            category: 'widgets',
            icon: 'awards',
            attributes: def.attributes,
            edit: function (props) {
                var controls = Object.keys(def.attributes).map(function (key) {
                    var onChange = function (value) {
                        var change = {};
                        change[key] = value;
                        props.setAttributes(change);
                    };
                    if (def.attributes[key].type === 'number') {
                        return el(RangeControl, { key: key, label: labels[key] || key, value: props.attributes[key], min: 1, max: 10, onChange: onChange });
                    }
                    return el(TextControl, { key: key, label: labels[key] || key, value: props.attributes[key], onChange: onChange });
                });

                return el(
                    Fragment,
                    null,
                    controls.length ? el(InspectorControls, null, el(PanelBody, { title: 'Settings' }, controls)) : null,
                    el('div', useBlockProps(), el(ServerSideRender, { block: name, attributes: props.attributes }))
                );
            },
            save: function () {
                return null;
            }
        });
    });
})(window.wp, window.SSC_BLOCKS);
