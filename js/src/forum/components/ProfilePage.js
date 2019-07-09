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
import username from 'flarum/helpers/username';
import UserPage from 'flarum/components/UserPage';

export default class ProfilePage extends UserPage {
    init() {
        super.init();

        this.loading = true;

        this.loadUser(app.session.user.username())
    }

    content() {
        return (
            app.session.user.ignoredUsers().map(user => {
                console.log(user);
                return (
                    <div className="PostUser">
                        <h3>{avatar(user, {className: 'PostUser-avatar'})} {username(user)}</h3>
                    </div>
                )
            })
        )
    }

    show(user) {
        this.user = app.session.user;

        m.redraw();
    }
}