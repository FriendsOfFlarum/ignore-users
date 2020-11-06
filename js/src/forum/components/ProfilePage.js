/*
 *
 *  This file is part of fof/ignore-users.
 *
 *  Copyright (c) 2019 FriendsOfFlarum..
 *
 *  For the full copyright and license information, please view the license.md
 *  file that was distributed with this source code.
 *
 */

import avatar from 'flarum/helpers/avatar';
import Button from 'flarum/components/Button';
import username from 'flarum/helpers/username';
import UserPage from 'flarum/components/UserPage';
import Stream from 'flarum/utils/Stream';

export default class ProfilePage extends UserPage {
    oninit(vdom) {
        super.oninit(vdom);

        this.loading = true;

        this.ignoredUsers = app.session.user.ignoredUsers();

        this.loadUser(app.session.user.username())
    }

    content() {
        return (
            <table className="NotificationGrid">
                {this.ignoredUsers.map((user, i) => {
                    var unignore = () => {
                        if (confirm(app.translator.trans(`fof-ignore-users.forum.user_controls.unignore_confirmation`))) {
                            user.save({ignored: false});
                            this.ignoredUsers.splice(i, 1);
                            app.session.user.ignoredUsers = Stream(this.ignoredUsers)
                        }
                    }

                    return (
                        <tr>
                            <td>
                                <a href={app.route.user(user)} config={m.route}>
                                    <h3>{avatar(user, {className: 'ignorePage-avatar'})} {username(user)}</h3>
                                </a>
                            </td>
                            <td className="ignorePage-button">
                                {Button.component({
                                    icon: 'fas fa-comment',
                                    type: 'button',
                                    className: 'Button Button--warning',
                                    onclick: unignore.bind(user),
                                }, app.translator.trans('fof-ignore-users.forum.user_controls.unignore_button'))}
                            </td>
                        </tr>
                    )
                })}
            </table>
        )
    }

    show(user) {
        this.user = app.session.user;

        m.redraw();
    }
}