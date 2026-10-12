import bootstrapAdmin from '@flarum/jest-config/src/bootstrap/admin';
import app from 'flarum/admin/app';
import PermissionGrid from 'flarum/admin/components/PermissionGrid';
import extractText from 'flarum/common/utils/extractText';
import { extend } from '../../../src/admin';
import loadTranslations from '../loadTranslations';

beforeAll(() => {
  bootstrapAdmin();
  loadTranslations();
  app.bootExtensions({ 'fof-ignore-users': { extend } });
  app.boot();
});

it('lets admins choose who cannot be ignored, alongside the reply permissions', () => {
  const replyPermissions = new PermissionGrid().replyItems().toArray();

  expect(
    replyPermissions.map((item) => ({ permission: 'permission' in item ? item.permission : undefined, label: extractText(item.label) }))
  ).toContainEqual({
    permission: 'notBeIgnored',
    label: 'Can not be ignored',
  });
});
