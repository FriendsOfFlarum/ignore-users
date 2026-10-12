import app from 'flarum/forum/app';
import UserPage from 'flarum/forum/components/UserPage';
import listItems from 'flarum/common/helpers/listItems';
import type User from 'flarum/common/models/User';
import mq from 'mithril-query';
import bootForum, { NOT_IGNORED, VIEWER } from './boot';

beforeAll(() => bootForum());

/** The profile navigation shown on a user's page, as [label, href] pairs. */
function profileNav(userId: string): [string, string | null][] {
  const page = new UserPage();
  page.user = app.store.getById<User>('users', userId)!;

  const out = mq(<ul>{listItems(page.navItems().toArray())}</ul>);

  return Array.from(out.rootEl.querySelectorAll('a')).map((link: Element) => [link.textContent!.trim(), link.getAttribute('href')]);
}

it('links to the ignored users page from the viewer’s own profile', () => {
  expect(profileNav(VIEWER)).toContainEqual(['Ignored Users', expect.stringMatching(/\/ignoredUsers$/)]);
});

it("doesn't link to it from someone else's profile", () => {
  expect(profileNav(NOT_IGNORED).map(([label]) => label)).not.toContain('Ignored Users');
});
