import UserPage from 'flarum/forum/components/UserPage';
import type Mithril from 'mithril';
import type User from 'flarum/common/models/User';
export default class ProfilePage extends UserPage {
    loading: boolean;
    ignoredUsers: User[];
    oninit(vnode: Mithril.Vnode): void;
    content(): Mithril.Children;
    show(user: User): void;
}
