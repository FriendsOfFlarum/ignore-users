import { extend } from 'flarum/extend';
import UserControls from 'flarum/utils/UserControls';
import Button from 'flarum/components/Button';

export default function() {
  extend(UserControls, 'userControls', function(items, user) {
    if (app.session.user === user || !app.session.user) {
      return;
    }

    function unignore() {
      if (confirm(app.translator.trans(`fof-ignore-users.forum.user_controls.unignore_confirmation`))) {
        this.save({ignored: false});
      }
    }

    function ignore() {
      if (confirm(app.translator.trans(`fof-ignore-users.forum.user_controls.ignore_confirmation`))) {
        this.save({ignored: true});
      }
    }

    if (user.ignored()) {
      items.add('unignore', Button.component({
        icon: 'fas fa-comment',
        onclick: unignore.bind(user),
      }, app.translator.trans('fof-ignore-users.forum.user_controls.unignore_button')));
    } else {
      items.add('ignore', Button.component({
        icon: 'fas fa-comment-slash',
        onclick: ignore.bind(user),
      }, app.translator.trans('fof-ignore-users.forum.user_controls.ignore_button')));
    }
  });
}
