import app from 'flarum/forum/app';
import avatar from 'flarum/common/helpers/avatar';
import Button from 'flarum/common/components/Button';
import username from 'flarum/common/helpers/username';
import UserPage from 'flarum/forum/components/UserPage';
import Stream from 'flarum/common/utils/Stream';
import Link from 'flarum/common/components/Link';
import Placeholder from 'flarum/common/components/Placeholder';

export default class ProfilePage extends UserPage {
  oninit(vdom) {
    super.oninit(vdom);

    this.loading = true;

    this.ignoredUsers = app.session.user.ignoredUsers();

    this.loadUser(app.session.user.username());
  }

  content() {
    if (this.ignoredUsers.length === 0) {
      return (
        <div className="IgnoresUserPage">
          <Placeholder text={app.translator.trans('fof-ignore-users.forum.user.ignored_users_empty_text')} />
        </div>
      );
    }

    return (
      <table className="NotificationGrid">
        {this.ignoredUsers.map((user, i) => {
          const unignore = () => {
            if (confirm(app.translator.trans(`fof-ignore-users.forum.user_controls.unignore_confirmation`))) {
              user.save({ ignored: false });
              this.ignoredUsers.splice(i, 1);
              app.session.user.ignoredUsers = Stream(this.ignoredUsers);
            }
          };

          return (
            <tr className="ignorePage-user">
              <td>
                <Link href={app.route.user(user)}>
                  {avatar(user, { className: 'ignorePage-avatar' })}
                  {username(user)}
                </Link>
              </td>
              <td className="ignorePage-button">
                {Button.component(
                  {
                    icon: 'fas fa-comment',
                    type: 'button',
                    className: 'Button Button--warning',
                    onclick: unignore.bind(user),
                  },
                  app.translator.trans('fof-ignore-users.forum.user_controls.unignore_button')
                )}
              </td>
            </tr>
          );
        })}
      </table>
    );
  }

  show(user) {
    this.user = app.session.user;

    m.redraw();
  }
}
