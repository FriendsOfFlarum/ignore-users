import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import mq from 'mithril-query';
import ProfilePage from '../../../src/forum/components/ProfilePage';
import bootForum, { IGNORED, NOT_IGNORED, VIEWER } from './boot';

beforeAll(() => bootForum());

afterEach(() => jest.restoreAllMocks());

/** Stands in for the API: answers the page's request for the viewer's list, and any save. */
function fakeApi(ignoredUserIds: string[]) {
  return jest
    .spyOn(app, 'request')
    .mockImplementation(((options: any) =>
      Promise.resolve(
        options.method === 'GET'
          ? { data: { type: 'users', id: VIEWER, relationships: { ignoredUsers: { data: ignoredUserIds.map((id) => ({ type: 'users', id })) } } } }
          : { data: { type: 'users', id: options.url.split('/').pop(), attributes: {} } }
      )) as never);
}

const settle = () => new Promise((resolve) => setTimeout(resolve));

async function openPage(ignoredUserIds: string[]) {
  const request = fakeApi(ignoredUserIds);

  // jsdom has no layout, so core's affixed sidebar can't measure itself; it is core's profile nav, not this page's UI.
  jest.spyOn(ProfilePage.prototype, 'sidebar').mockReturnValue(null);

  const out = mq(ProfilePage, {});
  await settle();
  out.redraw();

  return { out, request };
}

const text = (out: { rootEl: HTMLElement }): string => out.rootEl.textContent ?? '';

const sent = (request: ReturnType<typeof fakeApi>) =>
  request.mock.calls
    .map(([options]: any[]) => options)
    .filter((options) => options.method !== 'GET')
    .map((options) => ({ method: options.method, url: options.url, attributes: options.body.data.attributes }));

it('loads the list from the server when opened', async () => {
  const { request } = await openPage([IGNORED]);

  expect(request).toHaveBeenCalledWith(
    expect.objectContaining({
      method: 'GET',
      url: expect.stringMatching(/\/users\/2$/),
      params: expect.objectContaining({ include: 'ignoredUsers' }),
    })
  );
});

it('lists the users the viewer ignores, each with an unignore button', async () => {
  const { out } = await openPage([IGNORED, NOT_IGNORED]);

  expect(text(out)).toContain('User 3');
  expect(text(out)).toContain('User 4');
  expect(out).toHaveElement('.ignorePage-button button');
});

it('says so when the viewer ignores no one', async () => {
  const { out } = await openPage([]);

  expect(text(out)).toContain("It looks like you're not ignoring anyone.");
});

it("shows the server's latest list each time the page is opened", async () => {
  const first = await openPage([IGNORED]);
  jest.spyOn(window, 'confirm').mockReturnValue(true);
  first.out.click('.ignorePage-button button');
  jest.restoreAllMocks();

  const { out } = await openPage([NOT_IGNORED]);

  expect(text(out)).toContain('User 4');
});

describe('unignoring from the page', () => {
  async function unignore(confirmed: boolean) {
    const page = await openPage([IGNORED]);
    jest.spyOn(window, 'confirm').mockReturnValue(confirmed);

    page.out.click('.ignorePage-button button');

    return page;
  }

  it('unignores the user once the viewer confirms', async () => {
    const { request } = await unignore(true);

    expect(sent(request)).toEqual([{ method: 'PATCH', url: expect.stringMatching(/\/users\/3$/), attributes: { ignored: false } }]);
  });

  it('removes the user from the list once the viewer confirms', async () => {
    const { out } = await unignore(true);

    expect(text(out)).not.toContain('User 3');
    expect(text(out)).toContain("It looks like you're not ignoring anyone.");
  });

  it('keeps the user listed when the viewer cancels', async () => {
    const { out, request } = await unignore(false);

    expect(sent(request)).toEqual([]);
    expect(text(out)).toContain('User 3');
  });
});
