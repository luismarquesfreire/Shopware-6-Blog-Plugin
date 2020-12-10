import { Component, Mixin } from 'src/core/shopware';
import template from './sas-blog-detail.html.twig';
import Criteria from 'src/core/data-new/criteria.data';
import './sas-blog-detail.scss';

import slugify from 'slugify';

const { mapPropertyErrors } = Shopware.Component.getComponentHelper();

Component.register('sas-blog-detail', {
    template,

    inject: ['repositoryFactory'],

    mixins: [Mixin.getByName('notification')],

    metaInfo() {
        return {
            title: this.$createTitle()
        };
    },

    data() {
        return {
            blog: null,
            maxMetaTitleCharacters: 150,
            remainMetaTitleCharactersText: "150 characters left.",
            configOptions: {},
            isLoading: true,
            repository: null,
            processSuccess: false
        };
    },

    created() {
        this.repository = this.repositoryFactory.create('sas_blog_entries');
        this.getBlog();
    },

    watch: {
        'blog.active': function() {
            return this.blog.active ? 1 : 0;
        },
        'blog.title': function(value) {
            if (typeof value !== 'undefined') {
                this.blog.slug = slugify(value, {
                    lower: true
                });
            }
        }
    },

    computed: {
        mediaItem() {
            return this.blog !== null ? this.blog.media : null;
        },

        mediaRepository() {
            return this.repositoryFactory.create('media');
        },

        ...mapPropertyErrors('blog', ['title', 'slug', 'teaser', 'authorId'])
    },

    methods: {
        getBlog() {
            const criteria = new Criteria();
            criteria.addAssociation('blogCategories');

            this.repository
                .get(this.$route.params.id, Shopware.Context.api, criteria)
                .then((entity) => {
                    this.blog = entity;
                    this.isLoading = false;
                });
        },

        changeLanguage() {
            this.getBlog();
        },

        metaTitleCharCount() {
            if(this.blog.metaTitle.length > this.maxMetaTitleCharacters){
                this.remainMetaTitleCharactersText = "Exceeded "+this.maxMetaTitleCharacters+" characters limit.";
            }else{
                const remainCharacters = this.maxMetaTitleCharacters - this.blog.metaTitle.length;
                this.remainMetaTitleCharactersText = `${remainCharacters} characters left.`;
            }
        },

        onClickSave() {
            if (!this.blog.blogCategories || this.blog.blogCategories.length === 0) {
                this.createNotificationError({
                    message: this.$tc('sas-blog.detail.notification.error.missingCategory')
                });

                return;
            }

            this.isLoading = true;

            this.repository
                .save(this.blog, Shopware.Context.api)
                .then(() => {
                    this.isLoading = false;
                    this.$router.push({ name: 'blog.module.detail', params: {id: this.blog.id} });

                    this.createNotificationSuccess({
                        title: this.$tc('sas-blog.detail.notification.save-success.title'),
                        message: this.$tc('sas-blog.detail.notification.save-success.text')
                    });
                })
                .catch(exception => {
                    this.isLoading = false;
                });
        },

        onCancel() {
            this.$router.push({ name: 'blog.module.index' });
        },

        onSetMediaItem({ targetId }) {
            this.mediaRepository.get(targetId, Shopware.Context.api).then((updatedMedia) => {
                this.blog.mediaId = targetId;
                this.blog.media = updatedMedia;
            });
        },

        onRemoveMediaItem() {
            this.blog.mediaId = null;
            this.blog.media = null;
        },

        onMediaDropped(dropItem) {
            this.onSetMediaItem({ targetId: dropItem.id });
        },

        openMediaSidebar() {
            this.$parent.$parent.$parent.$parent.$refs.mediaSidebarItem.openContent();
        },

    }
});
