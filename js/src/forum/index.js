import Model from 'flarum/Model';
import User from 'flarum/models/User';
import addIgnoreUserControlButton from './addIgnoreUserControlButton';
import addHideIgnoredPost from './addHideIgnoredPost';

app.initializers.add('fof-ignore-users', function(app) {
  User.prototype.ignored = Model.attribute('ignored');

  addIgnoreUserControlButton();
  addHideIgnoredPost();
});
