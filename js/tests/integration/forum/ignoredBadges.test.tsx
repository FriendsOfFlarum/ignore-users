import app from 'flarum/forum/app';
import listItems from 'flarum/common/helpers/listItems';
import type Discussion from 'flarum/common/models/Discussion';
import type User from 'flarum/common/models/User';
import mq from 'mithril-query';
import bootForum, { IGNORED, NOT_IGNORED } from './boot';

function discussion(id: string, userId: string) {
  return {
    type: 'discussions',
    id,
    attributes: { title: `Discussion ${id}`, slug: id },
    relationships: { user: { data: { type: 'users', id: userId } } },
  };
}

beforeAll(() => {
  bootForum({ resources: [discussion('1', IGNORED), discussion('2', NOT_IGNORED)] });
});

/** The labels of the "ignored" badges, as the rendered tooltips announce them. */
function ignoredBadgeLabels(model: User | Discussion): string[] {
  const out = mq(<ul>{listItems(model.badges().toArray())}</ul>);

  return Array.from(out.rootEl.querySelectorAll('.Badge--ignored')).map((badge: Element) => badge.getAttribute('aria-label') ?? '');
}

it('marks an ignored user with a badge', () => {
  expect(ignoredBadgeLabels(app.store.getById<User>('users', IGNORED)!)).toEqual(['Ignored user']);
});

it("doesn't mark a user the viewer doesn't ignore", () => {
  expect(ignoredBadgeLabels(app.store.getById<User>('users', NOT_IGNORED)!)).toEqual([]);
});

it('marks a discussion started by an ignored user', () => {
  expect(ignoredBadgeLabels(app.store.getById<Discussion>('discussions', '1')!)).toEqual(['Started by an ignored user']);
});

it("doesn't mark a discussion started by someone the viewer doesn't ignore", () => {
  expect(ignoredBadgeLabels(app.store.getById<Discussion>('discussions', '2')!)).toEqual([]);
});
