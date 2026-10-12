import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import UserControls from 'flarum/forum/utils/UserControls';
import listItems from 'flarum/common/helpers/listItems';
import type User from 'flarum/common/models/User';
import CommentPost from 'flarum/forum/components/CommentPost';
import type Post from 'flarum/common/models/Post';
import mq from 'mithril-query';
import bootForum, { IGNORED, NOT_IGNORED } from './boot';

function post(id: string, userId: string, attributes: Record<string, unknown> = {}) {
  return {
    type: 'posts',
    id,
    attributes: {
      number: Number(id),
      contentType: 'comment',
      contentHtml: `<p>Post ${id} body</p>`,
      createdAt: '2026-10-01T10:00:00Z',
      ...attributes,
    },
    relationships: { discussion: { data: { type: 'discussions', id: '1' } }, user: { data: { type: 'users', id: userId } } },
  };
}

beforeAll(() => {
  bootForum({
    resources: [
      {
        type: 'discussions',
        id: '1',
        attributes: { title: 'A discussion', slug: '1-a-discussion' },
        relationships: { user: { data: { type: 'users', id: NOT_IGNORED } } },
      },
      post('1', IGNORED),
      post('2', NOT_IGNORED),
      post('3', IGNORED, { isHidden: true, hiddenAt: '2026-10-01T11:00:00Z' }),
    ],
  });
});

function render(id: string) {
  return mq(CommentPost, { post: app.store.getById<Post>('posts', id)! });
}

it("collapses an ignored user's post", () => {
  expect(render('1')).toHaveElement('.CommentPost.Post--hidden');
});

it("does not collapse a post by someone the viewer doesn't ignore", () => {
  expect(render('2')).not.toHaveElement('.Post--hidden');
});

it("offers a labelled toggle on an ignored user's post", () => {
  expect(render('1')).toHaveElementAttr('.item-ignore-toggle button', 'aria-label', 'Show or hide post from ignored user');
});

it("does not offer the toggle on a post by someone the viewer doesn't ignore", () => {
  expect(render('2')).not.toHaveElement('.item-ignore-toggle');
});

it('reveals the post when the toggle is clicked', () => {
  const out = render('1');

  out.click('.item-ignore-toggle button');
  out.redraw();

  expect(out).toHaveElement('.CommentPost.revealContent');
});

it("leaves a deleted post's own toggle to core", () => {
  expect(render('3')).not.toHaveElement('.item-ignore-toggle');
});

it('collapses a post already on screen once the viewer ignores its author', () => {
  const out = render('2');
  expect(out).not.toHaveElement('.Post--hidden');

  // Ignore the author from their user controls, as on their profile.
  jest.spyOn(window, 'confirm').mockReturnValue(true);
  jest.spyOn(app, 'request').mockResolvedValue({ data: { type: 'users', id: NOT_IGNORED, attributes: {} } } as never);
  const author = app.store.getById<User>('users', NOT_IGNORED)!;
  const controls = mq(<ul>{listItems(UserControls.controls(author, null).toArray())}</ul>);
  Array.from(controls.rootEl.querySelectorAll('button'))
    .find((button) => button.textContent!.trim() === 'Ignore')!
    .click();

  out.redraw();

  expect(out).toHaveElement('.CommentPost.Post--hidden');

  jest.restoreAllMocks();
  author.pushAttributes({ ignored: false });
});
