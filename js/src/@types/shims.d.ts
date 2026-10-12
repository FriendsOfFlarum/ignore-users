import User from 'flarum/common/models/User';

declare module 'flarum/common/models/User' {
  export default interface User {
    ignored(): boolean;
    canBeIgnored(): boolean;
    ignoredUsers(): User[] | undefined;

    /** From flarum/messages, when it is enabled. */
    canSendAnyMessage(): boolean | undefined;
  }
}
