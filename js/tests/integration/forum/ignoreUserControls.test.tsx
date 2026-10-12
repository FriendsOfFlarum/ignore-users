import { jest } from '@jest/globals';
import app from 'flarum/forum/app';
import UserControls from 'flarum/forum/utils/UserControls';
import listItems from 'flarum/common/helpers/listItems';
import type User from 'flarum/common/models/User';
import mq from 'mithril-query';
import bootForum, { CANNOT_BE_IGNORED, IGNORED, NOT_IGNORED, VIEWER } from './boot';

beforeAll(() => bootForum());

// Saving updates the user straight away, so put everyone back as the fixture had them.
beforeEach(() => {
  app.store.getById<User>('users', IGNORED)!.pushAttributes({ ignored: true });
  app.store.getById<User>('users', NOT_IGNORED)!.pushAttributes({ ignored: false });
});

afterEach(() => jest.restoreAllMocks());

function controls(userId: string): HTMLButtonElement[] {
  const user = app.store.getById<User>('users', userId)!;
  const out = mq(<ul>{listItems(UserControls.controls(user, null).toArray())}</ul>);

  return Array.from(out.rootEl.querySelectorAll('button'));
}

const labels = (userId: string) => controls(userId).map((button) => button.textContent!.trim());

/** Clicks a control, answering the confirmation dialog, and returns what was sent to the API. */
function clickAndCapture(userId: string, label: string, confirmed: boolean) {
  jest.spyOn(window, 'confirm').mockReturnValue(confirmed);
  const request = jest.spyOn(app, 'request').mockResolvedValue({ data: { type: 'users', id: userId, attributes: {} } } as never);

  controls(userId)
    .find((button) => button.textContent!.trim() === label)!
    .click();

  return request.mock.calls.map(([options]: any[]) => ({ method: options.method, url: options.url, attributes: options.body.data.attributes }));
}

it("offers to ignore a user the viewer doesn't ignore", () => {
  expect(labels(NOT_IGNORED)).toContain('Ignore');
  expect(labels(NOT_IGNORED)).not.toContain('Unignore');
});

it('offers to unignore a user the viewer ignores', () => {
  expect(labels(IGNORED)).toContain('Unignore');
  expect(labels(IGNORED)).not.toContain('Ignore');
});

it('offers nothing on the viewer themselves', () => {
  expect(labels(VIEWER)).not.toEqual(expect.arrayContaining(['Ignore']));
  expect(labels(VIEWER)).not.toEqual(expect.arrayContaining(['Unignore']));
});

it("offers nothing on a user who can't be ignored", () => {
  expect(labels(CANNOT_BE_IGNORED)).not.toEqual(expect.arrayContaining(['Ignore']));
});

it('offers nothing to a guest', () => {
  const viewer = app.session.user;
  app.session.user = null;

  try {
    expect(labels(NOT_IGNORED)).not.toEqual(expect.arrayContaining(['Ignore']));
  } finally {
    app.session.user = viewer;
  }
});

it('ignores the user once the viewer confirms', () => {
  expect(clickAndCapture(NOT_IGNORED, 'Ignore', true)).toEqual([
    { method: 'PATCH', url: expect.stringMatching(/\/users\/4$/), attributes: { ignored: true } },
  ]);
});

it('unignores the user once the viewer confirms', () => {
  expect(clickAndCapture(IGNORED, 'Unignore', true)).toEqual([
    { method: 'PATCH', url: expect.stringMatching(/\/users\/3$/), attributes: { ignored: false } },
  ]);
});

it('switches to unignore once the viewer has ignored the user', () => {
  clickAndCapture(NOT_IGNORED, 'Ignore', true);

  expect(labels(NOT_IGNORED)).toContain('Unignore');
});

it('does nothing when the viewer cancels', () => {
  expect(clickAndCapture(NOT_IGNORED, 'Ignore', false)).toEqual([]);
});
