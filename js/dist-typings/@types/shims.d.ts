import User from 'flarum/common/models/User';

declare module 'flarum/common/models/User' {
  export default interface User {
    ignored(): boolean;
    canBeIgnored(): boolean;
    ignoredUsers(): User[] | undefined;
  }
}
