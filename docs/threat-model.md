# Threat Model

English | [繁體中文](./threat-model.zh-TW.md)

## Designed to Mitigate

- accidental execution of dangerous commands by normal operators
- uncontrolled multi-user access to the same shell session
- blind operation without audit history
- exposing AI CLI tools only to users who do not have SSH access
- using web UX while still keeping OS-level least privilege

## Not Designed to Fully Mitigate

- a fully compromised WordPress admin environment
- kernel-level or root-level host compromise
- malicious code already running as the runtime OS user
- every shell metacharacter or command obfuscation trick
- all forms of data exfiltration from a permitted runtime account

## Important Boundary

The key boundary is the runtime OS user.

If the runtime user can read or write a path, the CLI can usually do the same.

That means your first security review should be:

- filesystem permissions
- group membership
- `sudo` access
- network egress

## Operational Risks

### 1. Bridge too broad

If the bridge allows arbitrary shell commands, the bridge becomes the product's main weakness.

### 2. Web-only checks

If elevated verification exists only in JavaScript, it can be bypassed.

### 3. Shared writable directories

If the runtime account shares broad writable access with the web server user, the separation loses value.

### 4. Missing session lock

Two operators can corrupt the same interactive flow.

### 5. Missing audit trail

When something breaks, nobody knows who sent what.

## Safer Rollout Path

1. Start readonly
2. Add restricted key send
3. Add normal text send
4. Add audit logs and locks
5. Add elevated verification
6. Only then consider provider-specific helpers
