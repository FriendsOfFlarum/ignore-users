import { extend } from 'flarum/extend';
import app from 'flarum/app';
import PermissionGrid from 'flarum/components/PermissionGrid';

app.initializers.add('fof-ignore-users', () => {
  extend(PermissionGrid.prototype, 'replyItems', items => {
    items.add('notBeIgnored', {
      icon: 'fas fa-comment-slash',
      label: app.translator.trans('fof-ignore-users.admin.permissions.can_not_be_ignored_label'),
      permission: 'notBeIgnored'
    });
  });
});
