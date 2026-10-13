import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import type Mithril from 'mithril';
import type MessageStream from 'ext:flarum/messages/forum/components/MessageStream';

/**
 * flarum/messages leaves the reply box out of a conversation the viewer can't
 * send in, without a word. Say so, but not why: being ignored isn't announced.
 */
export default function addCannotSendNote() {
  extend('ext:flarum/messages/forum/components/MessageStream', 'content', function (this: MessageStream, items: Mithril.Children[]) {
    const dialog = this.attrs.dialog;
    const recipient = dialog.recipient();

    // Messages explains it itself when the other member can't reply, and tells someone who can't send at all nothing.
    if (dialog.canSendMessage() || !app.session.user?.canSendAnyMessage() || !recipient?.canSendAnyMessage()) {
      return;
    }

    items.push(
      <div className="MessageStream-item" key="cannotSend">
        <p className="helpText">{app.translator.trans('fof-ignore-users.forum.messages.cannot_send_text')}</p>
      </div>
    );
  });
}
