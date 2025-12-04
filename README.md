# Anonymization of user data after deletion #

# Description #

**Anonymization of user data after deletion** is a "tool plugin" that anonymizes deleted users in Moodle. 
There are two ways to trigger the anonymization process:

1. **Event trigger**: When a user is deleted, anonymization is automatically triggered for that user.
2. **Manual anonymization**: Administrators can manually trigger anonymization for all previously deleted users.

If user fields are not deleted during account deletion, they are anonymized as follows:

- The `userid` remains unchanged.
- The `firstname` is replaced with a random adjective.
- The `lastname` is replaced with a random animal name.
- The `username` is replaced with the new `firstname` + `lastname` + a timestamp.

# Installation #

1. Clone the repository into the `/admin/tool/user_anonymizer` directory of your Moodle installation:
2. Visit **Site administration → Notifications** to trigger the installation, or run `admin/cli/upgrade.php`.

### Voraussetzungen
- No external dependencies.

# Configuration #

After installation, the plugin can be configured via:
**Website-Administration → Plugins → Admin tools → Delay for anonymizing user data**

# Settings #

- Delay in days between deletion and anonymization of user data (default: 0). 
- Option to manually trigger the anonymization of already deleted user accounts.

# Usage #

Navigate to **Site administration → Plugins → Admin tools → Delay for anonymizing user data**.

Under **Site administration → Plugins → Admin tools → Immediate anonymization of all previously deleted users** admins can manually trigger anonymization for all users already marked as deleted.

# Capabilities #

| Capability name               | Description                                                           | Default role     |
| ----------------------------- | --------------------------------------------------------------------- | ---------------- |
| `tool/user_anonymizer:manage` | Allows users to configure settings and manually trigger anonymization | Managers, Admins |


# Cronjobs #

| Task Class                                          | Description                                                 | Default execution interval |
| --------------------------------------------------- | ----------------------------------------------------------- | -------------------------- |
| `tool_user_anonymizer\task\scheduled_anonymization` | Retrieves users marked for anonymization and processes them | Several times per day      |

# Web Services #

This plugin does not provide any web service functions.

# License #

This plugin is lecensed under the [GNU General Public License v3.0](https://www.gnu.org/licenses/gpl-3.0.en.html).

# Credits #

- Author: Ramona Rommel (Ramona.rommel@oncampus.de)