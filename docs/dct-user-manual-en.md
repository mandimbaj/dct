# User Manual - Data Capture Tool v.2

Last updated: October 2, 2026

## 1. Purpose of the tool

Data Capture Tool v.2 helps country and regional teams enter, import, review, approve, archive, and use health data. This version keeps the same reference database while adding a more structured interface, finer permissions, notifications, and data integration through files and APIs.

## 2. Access and sign-in

1. Open the production DCT URL.
2. On the Microsoft page, enter the WHO work email address.
3. Click Next, then follow the Microsoft instructions: password, multifactor verification or mobile approval when requested.
4. After sign-in, confirm the active country in the header.
5. Use the user menu to change language, open the profile, or sign out.

## 3. Dashboard

The dashboard gives a quick view of data volumes:

- locations;
- indicators;
- approved, pending, and rejected indicator values;
- archived values;
- publications;
- users;
- summary charts.

Country users see only their country data. Regional administrators and super administrators can monitor regional data according to their permissions.

## 4. Global search and navigation

1. Use the top search bar to find a menu or resource quickly.
2. Use the side navigation to open the main modules.
3. Inside each module, use the submenus to move between data, references, and tools.
4. Wide tables keep the first column visible while scrolling horizontally so users do not lose context.

## 5. Messages and notifications

Two areas are available in the header:

- Messages: communications sent to concerned users.
- Notifications: system events, new data, pending validation items, and important creations or updates.

Workflow:

1. Click the message or notification icon.
2. Click an item to open it.
3. Once read, the item is removed from the active list.
4. In production, the same alerts can also be sent by email to concerned regional and country administrators.

## 6. Indicators

The Indicators module manages indicator definitions and indicator values.

### Add an indicator value

1. Open Indicators > Indicator values.
2. Click Add indicator value.
3. Select the indicator, country, period, source, measure method, and disaggregation options.
4. Enter the value.
5. Save.
6. The value remains pending if it requires validation.

### Import several values

1. Open Indicators > Indicator values.
2. Click Import data.
3. Download the Excel template.
4. Fill in the template. Reference fields provide selection lists.
5. Upload the file.
6. Correct any duplicate or invalid rows reported by the system.
7. Imported values are created as pending records for approval.

### Approve or reject

1. Open the record.
2. Click Approve to automatically set the approval status to Approved.
3. Click Reject if the value must be refused.
4. Notifications inform the concerned users.

### Archives

1. Open Indicators > Archives.
2. Review archived values from `fact_data_archive`.
3. A super administrator can edit an archived value when a correction is required.
4. The displayed fields remain aligned with the indicator values module.

## 7. Data Integration

The Data Integration module imports data from external systems that do not always have the same structure as the DCT.

### Create a connection

1. Open Data Integration > Connections.
2. Click Create.
3. Choose the provider: DHIS2, DataBank, WHO DataHub, or another source.
4. Choose the method: direct server connection or API.
5. Enter the server URL, authentication type, username, API token, or API key as required.
6. Save the connection.

Passwords, tokens, API keys, and secrets are encrypted in the database.

### Field mapping

Before validation, each connection must define how external fields match DCT fields.

Mapping types:

| Type | Purpose | Example |
| --- | --- | --- |
| Direct | Copy the external value as-is | `value` to `value_received` |
| Lookup | Find a DCT reference from an external code or label | DHIS2 `dx` to `indicator_id` |
| Computed | Calculate or infer a value | extract `start_period` from `pe` |
| Default value | Use a fixed value when the source does not provide one | default data source |
| Transform | Apply a conversion | divide by 1000, normalize a year |

Workflow:

1. Open the connection.
2. Go to Field mappings.
3. For every required local field, choose the matching external field.
4. Select the mapping type.
5. Add a default value when needed.
6. Save.
7. Test the import.

### DHIS2 import

1. Create a DHIS2 connection.
2. Enter the URL, username, or API token.
3. Map the key fields: indicator, country, period, value, data source, category option, and measure method.
4. Run the import.
5. Review the created rows under Indicator values.
6. An administrator then approves the data.

## 8. Data Quality

The Data Quality module helps detect and correct problems:

- missing values;
- internal consistency issues;
- external consistency issues;
- multiple measures;
- invalid sources;
- invalid categories;
- invalid periods.

Workflow:

1. Open Data Quality.
2. Select the required check.
3. Use filters to find anomalies.
4. Click Correct when the action is available.
5. Update the data or reference.
6. Save and run the check again.

## 9. Publications

The Publications module manages knowledge products, analytical documents, and resources.

Workflow:

1. Open Publications > Knowledge products.
2. Click Create.
3. Enter the title, type, category, domain, country, authors, date, summary, and other required fields.
4. Upload the internal file and cover image from the local machine.
5. Save.

Published files are stored in the storage area configured for the DCT.

## 10. Facilities

The Facilities module manages facilities and related service data.

Main submenus:

- Health facilities;
- Service capacity;
- Service readiness;
- Service availability;
- Facility ownership;
- Facility types;
- Service areas;
- Service domains;
- Service interventions;
- Provision units.

Import workflow:

1. Open Health facilities.
2. Download the Excel template.
3. Fill in the columns. Columns linked to references provide selection lists.
4. Upload the file.
5. Correct reported duplicates or errors.
6. Save the valid records.

## 11. Health Workforce

The Health Workforce module manages workforce values, cadres, institutions, training information, and knowledge products related to human resources for health.

1. Open Health Workforce.
2. Choose the submenu.
3. Add or import data.
4. Use filters to review data by country, period, or category.
5. Approve data if the workflow requires validation.

## 12. UHC Clock

The UHC Clock module tracks priority Universal Health Coverage indicators.

How it works:

1. Administrators define UHC themes, groups, and indicators.
2. Countries select their priority indicators.
3. The system calculates progress from current and archived data.
4. National data sources are prioritized. If no national source is available, an international source can be used.
5. The Progress page displays country results or only the signed-in user's country.
6. Click a country to view evaluated indicators, UHC Clock model levels, and the data source.

## 13. Regions and locations

This module manages the geographic structure:

- region;
- country;
- administrative levels;
- codes;
- income groups;
- special statuses.

Reference fields should be selected from dropdown lists rather than entered as numbers.

## 14. Authentication, roles, and permissions

This module manages users, roles, and permissions.

Workflow:

1. Open Authentication > Users to create or edit a user.
2. Assign a country when the user belongs to a country.
3. Open Authentication > Roles and permissions.
4. Define visible modules and allowed actions: view, create, update, delete, approve, import, export.
5. Save.
6. Test with a non-super-admin account to confirm unauthorized menus are hidden.

## 15. API Tokens

The API Tokens module prepares controlled API access.

1. Open API Tokens.
2. Create a token for the authorized integration.
3. Define its scope.
4. Store the token securely.
5. Revoke the token when it is no longer used.

## 16. Good practices

- Always import data first as pending records.
- Approve data only after review.
- Use dropdown lists to avoid code mistakes.
- Use official templates to avoid duplicates.
- Check notifications after major imports.
- Never share API tokens, passwords, or private links outside authorized channels.
