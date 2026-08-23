# Events and Broadcasting

## Broadcasting Driver
- **Driver:** Laravel Reverb (WebSocket server)
- **Port:** 8080
- **Config:** `config/reverb.php`
- **Client:** Laravel Echo + pusher-js

## Events

### MessageSent
**File:** `app/Events/MessageSent.php`
- **Implements:** `ShouldBroadcastNow`
- **Properties:** `$message` (ChatMessage model), `$conversationId`
- **Channel:** `private conversation.{conversationId}`
- **Payload:** Full ChatMessage data including attachment info

### MessageRead
**File:** `app/Events/MessageRead.php`
- **Implements:** `ShouldBroadcastNow`
- **Properties:** `$conversationId`, `$readByUserId`
- **Channel:** `private conversation.{conversationId}`

## Broadcast Channels
| Channel | Type | Auth Logic |
|---------|------|-----------|
| `App.Models.User.{id}` | private | `user.id === {id}` |
| `conversation.{id}` | private | user.id found in hyphen-separated conversation ID |
| `online` | presence | Returns user id+name for presence tracking |

## Conversation ID Format
- Format: `{userId1}-{userId2}` (sorted numerically)
- Example: `1-5` (admin=1, patient=5)
- Both participants can subscribe to the same channel

## Client Configuration
```javascript
// resources/js/echo.js
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT,
    wssPort: import.meta.env.VITE_REVERB_PORT,
    forceTLS: false,
});
```

## Related Pages
- [[Controllers]]
- [[Architecture]]
