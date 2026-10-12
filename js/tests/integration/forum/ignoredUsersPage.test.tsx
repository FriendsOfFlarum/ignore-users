import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import type User from 'flarum/common/models/User';
import mq from 'mithril-query';
import ProfilePage from '../../../src/forum/components/ProfilePage';
import bootForum, { IGNORED, VIEWER } from './boot';

beforeAll(() => bootForum());

function setIgnoredUsers(ids: string[]) {
  const viewer = app.session.user!;

  // Unignoring from the page swaps the method for a stream on the instance; drop it to read the relationship again.
  delete (viewer as any).ignoredUsers;
  viewer.pushData({ relationships: { ignoredUsers: { data: ids.map((id) => ({ type: 'users', id })) } } });
}

beforeEach(() => setIgnoredUsers([IGNORED]));

afterEach(() => jest.restoreAllMocks());

const text = (out: ReturnType<typeof renderPage>): string => out.rootEl.textContent ?? '';

function renderPage() {
  // jsdom has no layout, so core's affixed sidebar can't measure itself; it is core's profile nav, not this page's UI.
  jest.spyOn(ProfilePage.prototype, 'sidebar').mockReturnValue(null);

  return mq(ProfilePage, {});
}

it('lists the users the viewer ignores, each with an unignore button', () => {
  const out = renderPage();

  expect(text(out)).toContain('User 3');
  expect(out).toHaveElement('.ignorePage-button button');
});

it('says so when the viewer ignores no one', () => {
  setIgnoredUsers([]);

  expect(text(renderPage())).toContain("It looks like you're not ignoring anyone.");
});

describe('unignoring from the page', () => {
  function unignore(confirmed: boolean) {
    jest.spyOn(window, 'confirm').mockReturnValue(confirmed);
    const request = jest.spyOn(app, 'request').mockResolvedValue({ data: { type: 'users', id: IGNORED, attributes: {} } } as never);

    const out = renderPage();
    out.click('.ignorePage-button button');

    const sent = request.mock.calls.map(([options]: any[]) => ({
      method: options.method,
      url: options.url,
      attributes: options.body.data.attributes,
    }));

    return { out, sent };
  }

  it('unignores the user once the viewer confirms', () => {
    expect(unignore(true).sent).toEqual([{ method: 'PATCH', url: expect.stringMatching(/\/users\/3$/), attributes: { ignored: false } }]);
  });

  it('removes the user from the list once the viewer confirms', () => {
    const { out } = unignore(true);

    expect(text(out)).not.toContain('User 3');
    expect(text(out)).toContain("It looks like you're not ignoring anyone.");
  });

  it('keeps the user listed when the viewer cancels', () => {
    const { out, sent } = unignore(false);

    expect(sent).toEqual([]);
    expect(text(out)).toContain('User 3');
  });
});
