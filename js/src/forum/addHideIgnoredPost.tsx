import { extend } from 'flarum/common/extend';
import CommentPost from 'flarum/forum/components/CommentPost';
import Button from 'flarum/common/components/Button';
import ItemList from 'flarum/common/utils/ItemList';
import type Mithril from 'mithril';

export default function () {
  extend(CommentPost.prototype, 'elementAttrs', function (this: CommentPost, elementAttrs: Record<string, unknown>) {
    const user = this.attrs.post.user();
    const ignored = user && user.ignored();
    if (ignored) {
      (elementAttrs as any).className += ' Post--hidden';
    }
    return elementAttrs;
  });

  extend(CommentPost.prototype, 'headerItems', function (this: CommentPost, items: ItemList<Mithril.Children>) {
    const post = this.attrs.post;
    const user = post.user();

    if (post.isHidden() || !(user && user.ignored())) {
      return;
    }

    items.add(
      'ignore-toggle',
      <Button className="Button Button--default Button--more" icon="fas fa-ellipsis-h" onclick={this.toggleContent.bind(this)} />
    );
  });
}
