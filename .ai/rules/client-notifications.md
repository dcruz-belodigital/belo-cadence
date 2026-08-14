---
paths:
  - 'app/Actions/ClientNotifications/**'
---

# Client Notifications

## Sending invariants: claim before send, never recompute history
`SendClientNotificationAction` renders the message first (so the body can be snapshotted even when delivery fails), then claims the occurrence inside a transaction that locks the schedule and advances `next_send_at`, and only then hands the mail to the mailer outside any transaction. Never move the send inside the transaction: a rollback would erase the record of a message that already left. The unique index on `(client_notification_schedule_id, scheduled_for)` is the real duplicate guard; a `UniqueConstraintViolationException` means another process already handled that occurrence and must be swallowed. A failed send still advances the schedule (deterministic, no retry storms) and a one-time schedule closes when its occurrence is claimed, not when the send succeeds. Deliveries are snapshots: never recompute recipient, sender, subject or body from current data.
