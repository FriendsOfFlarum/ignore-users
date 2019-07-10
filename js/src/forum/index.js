import Model from 'flarum/Model';
import User from 'flarum/models/User';
import addIgnoreUserControlButton from './addIgnoreUserControlButton';
import addHideIgnoredPost from './addHideIgnoredPost';
import addProfilePage from './addProfilePage'
import ProfilePage from './components/ProfilePage';

app.initializers.add('fof-ignore-users', function(app) {
  User.prototype.ignored = Model.attribute('ignored');
  User.prototype.ignoredUsers = Model.hasMany('ignoredUsers');

  app.routes.ignoredUsers = {path: '/ignoredUsers', component: ProfilePage.component()};

  addIgnoreUserControlButton();
  addHideIgnoredPost();
  addProfilePage();
});
