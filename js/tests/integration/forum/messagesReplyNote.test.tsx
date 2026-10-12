import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import { makeUser } from '@flarum/jest-config/factory';
import mq from 'mithril-query';
import messagesExtend from 'ext:flarum/messages/common/extend';
import MessageStream from 'ext:flarum/messages/forum/components/MessageStream';
import MessageStreamState from 'ext:flarum/messages/forum/states/MessageStreamState';
import type Dialog from 'ext:flarum/messages/common/models/Dialog';
import bootForum, { VIEWER } from './boot';

const NOTE = "You can't send messages in this conversation.";

/** A conversation between the viewer and someone else, as flarum/messages serializes it. */
function conversation(id: string, otherUserId: string, canSendMessage: boolean) {
  return {
    type: 'dialogs',
    id,
    attributes: { title: `Conversation ${id}`, type: 'direct', canSendMessage },
    relationships: { users: { data: [VIEWER, otherUserId].map((userId) => ({ type: 'users', id: userId })) } },
  };
}

beforeAll(() => {
  bootForum({
    extensions: { 'flarum-messages': { extend: messagesExtend } },
    resources: [
      makeUser({ id: '7', attributes: { canSendAnyMessage: true } }),
      makeUser({ id: '8', attributes: { canSendAnyMessage: false } }),
      conversation('1', '7', false),
      conversation('2', '7', true),
      conversation('3', '8', false),
    ],
  });

  app.session.user!.pushAttributes({ canSendAnyMessage: true });

  // What flarum/messages' bundle does when it loads.
  flarum.reg.add('flarum-messages', 'forum/components/MessageStream', MessageStream);
});

// Marking the conversation read measures layout, which jsdom can't do; it is messages' own behaviour, not this one.
beforeEach(() => jest.spyOn(MessageStream.prototype, 'markAsRead').mockImplementation(() => {}));
afterEach(() => jest.restoreAllMocks());

function streamText(dialogId: string): string {
  const dialog = app.store.getById<Dialog>('dialogs', dialogId)!;
  const out = mq(MessageStream, { dialog, state: new MessageStreamState({ filter: { dialog: dialogId } }) });

  return out.rootEl.textContent ?? '';
}

it("says so where the reply box would be when the viewer can't send in a conversation", () => {
  expect(streamText('1')).toContain(NOTE);
});

it('says nothing when the viewer can send there', () => {
  expect(streamText('2')).not.toContain(NOTE);
});

it("leaves it to flarum/messages when the other member can't reply anyway", () => {
  expect(streamText('3')).not.toContain(NOTE);
});

it("says nothing to a viewer who can't send messages at all", () => {
  app.session.user!.pushAttributes({ canSendAnyMessage: false });

  try {
    expect(streamText('1')).not.toContain(NOTE);
  } finally {
    app.session.user!.pushAttributes({ canSendAnyMessage: true });
  }
});
