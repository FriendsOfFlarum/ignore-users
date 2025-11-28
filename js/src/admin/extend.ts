import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';

export default [
  new Extend.Admin() //
    .permission(
      () => ({
        icon: 'fas fa-comment-slash',
        label: app.translator.trans('fof-ignore-users.admin.permissions.can_not_be_ignored_label'),
        permission: 'notBeIgnored',
      }),
      'reply',
      60
    ),
];
