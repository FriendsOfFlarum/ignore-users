import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import Discussion from 'flarum/common/models/Discussion';
import User from 'flarum/common/models/User';
import Badge from 'flarum/common/components/Badge';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export default function addSubscriptionBadge() {
  extend(Discussion.prototype, 'badges', function (this: Discussion, badges: ItemList<Mithril.Children>) {
    let badge;

    const user = this.user();
    if (user && typeof user !== 'boolean' && user.ignored()) {
      badge = <Badge label={app.translator.trans('fof-ignore-users.forum.badge.discussion_label')} icon="fas fa-user-slash" type="ignored" />;
    }

    if (badge) {
      badges.add('user-discussion-ignored', badge);
    }
  });

  extend(User.prototype, 'badges', function (this: User, badges: ItemList<Mithril.Children>) {
    let badge;

    if (this.ignored()) {
      badge = <Badge label={app.translator.trans('fof-ignore-users.forum.badge.user_label')} icon="fas fa-user-slash" type="ignored" />;
    }

    if (badge) {
      badges.add('user-ignored', badge);
    }
  });
}
