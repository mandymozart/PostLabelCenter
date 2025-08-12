import template from './post-logo-icon.html.twig';

const {Component, Mixin} = Shopware;

Component.register('post-logo-icon', {
    template,

    mixins: [Mixin.getByName("plc-helper")],
});
