(function (blocks, el, editor, components) {
  'use strict';
  blocks.registerBlockType('expert-wp/quote', {
    title: 'Demande de devis', category: 'widgets', icon: 'email-alt',
    attributes: {mode: {type: 'string', default: 'quote'}},
    edit: function (props) {
      return el('div', editor.useBlockProps({className: 'ewp-editor-placeholder'}),
        el('h3', null, 'Votre formulaire de demande'),
        el('p', null, 'Les champs, la sécurité et les conditions sont gérés par l’extension. Le formulaire complet apparaît sur le site.'),
        el(components.SelectControl, {label: 'Type de demande', value: props.attributes.mode,
          options: [{label: 'Devis avec questions par prestation', value: 'quote'}, {label: 'Contact simple', value: 'contact'}],
          onChange: function (mode) { props.setAttributes({mode: mode}); }}));
    }, save: function () { return null; }
  });
  blocks.registerBlockType('expert-wp/confirmation', {
    title: 'Confirmation de demande', category: 'widgets', icon: 'yes-alt',
    edit: function () { return el('p', editor.useBlockProps(), 'Confirmation après enregistrement. Aucun contenu personnel ne sera affiché.'); },
    save: function () { return null; }
  });
})(window.wp.blocks, window.wp.element.createElement, window.wp.blockEditor, window.wp.components);
