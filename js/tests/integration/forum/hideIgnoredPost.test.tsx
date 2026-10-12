import app from 'flarum/forum/app';
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
