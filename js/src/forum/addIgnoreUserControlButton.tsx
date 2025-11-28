import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import UserControls from 'flarum/forum/utils/UserControls';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';

export default function () {
  extend(UserControls, 'userControls', function (items: ItemList<Mithril.Children>) {
    const user = arguments[1] as User;

    if (app.session.user === user || !app.session.user || !user.canBeIgnored()) {
      return;
    }

    function unignore(this: User) {
      if (confirm(extractText(app.translator.trans(`fof-ignore-users.forum.user_controls.unignore_confirmation`)))) {
        this.save({ ignored: false });
      }
    }

    function ignore(this: User) {
      if (confirm(extractText(app.translator.trans(`fof-ignore-users.forum.user_controls.ignore_confirmation`)))) {
        this.save({ ignored: true });
      }
    }

    if (user.ignored()) {
      items.add(
        'unignore',
        <Button icon="fas fa-comment" onclick={unignore.bind(user)}>
          {app.translator.trans('fof-ignore-users.forum.user_controls.unignore_button')}
        </Button>
      );
    } else {
      items.add(
        'ignore',
        <Button icon="fas fa-comment-slash" onclick={ignore.bind(user)}>
          {app.translator.trans('fof-ignore-users.forum.user_controls.ignore_button')}
        </Button>
      );
    }
  });
}
