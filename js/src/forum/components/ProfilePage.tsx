import app from 'flarum/forum/app';
import Avatar from 'flarum/common/components/Avatar';
import Button from 'flarum/common/components/Button';
import Link from 'flarum/common/components/Link';
import username from 'flarum/common/helpers/username';
import UserPage from 'flarum/forum/components/UserPage';
import Stream from 'flarum/common/utils/Stream';
import Placeholder from 'flarum/common/components/Placeholder';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';

export default class ProfilePage extends UserPage {
  loading!: boolean;
  ignoredUsers!: User[];

  oninit(vnode: Mithril.Vnode) {
    super.oninit(vnode);

    this.loading = true;

    this.ignoredUsers = app.session.user!.ignoredUsers() || [];

    this.loadUser(app.session.user!.username());

    this.loading = false;
  }

  content(): Mithril.Children {
    if (this.loading) {
      return (
        <div className="DiscussionList">
          <LoadingIndicator />
        </div>
      );
    }

    if (this.ignoredUsers.length === 0) {
      return (
        <div className="DiscussionList">
          <Placeholder text={app.translator.trans('fof-ignore-users.forum.profile_page.no_ignored')} />
        </div>
      );
    }

    return (
      <table className="NotificationGrid">
        {this.ignoredUsers.map((user, i) => {
          const unignore = () => {
            if (confirm(extractText(app.translator.trans(`fof-ignore-users.forum.user_controls.unignore_confirmation`)))) {
              user.save({ ignored: false });
              this.ignoredUsers.splice(i, 1);
              app.session.user!.ignoredUsers = Stream(this.ignoredUsers);
            }
          };

          return (
            <tr>
              <td>
                <Link href={app.route.user(user)}>
                  <h3>
                    <Avatar user={user} className="ignorePage-avatar" /> {username(user)}
                  </h3>
                </Link>
              </td>
              <td className="ignorePage-button">
                <Button icon="fas fa-comment" type="button" className="Button Button--warning" onclick={unignore.bind(user)}>
                  {app.translator.trans('fof-ignore-users.forum.user_controls.unignore_button')}
                </Button>
              </td>
            </tr>
          );
        })}
      </table>
    );
  }

  show(user: User) {
    this.user = app.session.user!;

    m.redraw();
  }
}
