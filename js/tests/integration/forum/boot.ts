import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { makeUser } from '@flarum/jest-config/factory';
import app from 'flarum/forum/app';
import flatten from 'flat';
import jsYaml from 'js-yaml';
import fs from 'fs';
import path from 'path';
import { extend } from '../../../src/forum';

/** The viewer (2) ignores user 3; user 4 is not ignored; user 5 cannot be ignored. */
export const VIEWER = '2';
export const IGNORED = '3';
export const NOT_IGNORED = '4';
export const CANNOT_BE_IGNORED = '5';

/**
 * Boots the forum through the extension's real entry point, so its initializer
 * and extenders run exactly as they do in production.
 */
export default function bootForum(options: { viewer?: string | null; resources?: any[] } = {}) {
  const viewer = options.viewer === undefined ? VIEWER : options.viewer;

  bootstrapForum({
    resources: [
      { type: 'forums', id: '1', attributes: { baseUrl: 'https://forum.test', basePath: '' } },
      makeUser({
        id: VIEWER,
        attributes: { ignored: false, canBeIgnored: false },
        relationships: { ignoredUsers: { data: [{ type: 'users', id: IGNORED }] } },
      }),
      makeUser({ id: IGNORED, attributes: { ignored: true, canBeIgnored: true } }),
      makeUser({ id: NOT_IGNORED, attributes: { ignored: false, canBeIgnored: true } }),
      makeUser({ id: CANNOT_BE_IGNORED, attributes: { ignored: false, canBeIgnored: false } }),
      ...(options.resources ?? []),
    ],
    session: { userId: viewer === null ? null : Number(viewer), csrfToken: 'test' },
  });

  app.bootExtensions({ 'fof-ignore-users': { extend } });
  app.boot();

  const locale = path.resolve(process.cwd(), '../resources/locale/en.yml');
  app.translator.addTranslations(flatten(jsYaml.load(fs.readFileSync(locale, 'utf8'))));
}
