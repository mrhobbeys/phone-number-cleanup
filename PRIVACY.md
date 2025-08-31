# Phone Number Clean Up - Privacy Considerations

## Overview
The Phone Number Clean Up plugin collects and stores phone numbers that are extracted from text submitted by users. This document outlines privacy considerations and provides recommended text to include in your site's privacy policy.

## Data Collection and Storage

### What data is collected:
- Phone numbers extracted from user-submitted text
- IP addresses of users who submit text for extraction
- User IDs (for logged-in users)
- Timestamps of when extractions occurred

### How data is stored:
- All extracted phone numbers are stored in the WordPress database
- For logged-in users, phone numbers are also associated with their user account
- Data is stored indefinitely until manually deleted

## Recommended Privacy Policy Text

You should include the following text in your site's privacy policy if you use this plugin:

---

### Phone Number Extraction

Our website uses the Phone Number Clean Up plugin to allow users to extract phone numbers from text. When you use this feature:

- Any phone numbers detected in your submitted text will be stored in our database
- Your IP address will be recorded alongside the extracted phone numbers
- If you are logged in, the extracted phone numbers will be associated with your user account
- Extracted phone numbers will be displayed to you on future visits if you are logged in
- Site administrators can view all extracted phone numbers

We use this information to:
- Provide you with a history of your extracted phone numbers when you are logged in
- Analyze usage patterns of the phone extraction feature
- Monitor for potential abuse of the system

This data is stored indefinitely unless you request deletion.

---

## Regulatory Considerations

### GDPR (European Union)
Under the General Data Protection Regulation:
- Phone numbers may be considered personal data
- You should provide a way for users to request deletion of their data
- You should disclose the data collection in your privacy policy
- Consider adding a checkbox for explicit consent before storing numbers

### CCPA/CPRA (California, USA)
Under the California Consumer Privacy Act and California Privacy Rights Act:
- You should disclose what personal information is collected
- Users have the right to know what personal information is collected about them
- Users have the right to delete personal information collected from them
- Consider providing a "Do Not Sell My Personal Information" option

### PIPEDA (Canada)
Under the Personal Information Protection and Electronic Documents Act:
- Obtain consent for the collection, use, and disclosure of personal information
- Limit collection to what is necessary for identified purposes
- Limit use, disclosure, and retention of personal information

### Other Jurisdictions
Many other jurisdictions have similar privacy laws. Research the specific requirements for:
- Australia (Privacy Act)
- Brazil (LGPD)
- Japan (APPI)
- And other regions where your site may have users

## Implementation Recommendations

1. Add a checkbox for consent before extraction
2. Implement a data deletion request mechanism
3. Consider a data retention policy to automatically delete older records
4. Provide transparency about how the data is used
5. Consider anonymizing IP addresses after a certain period

## Disclaimer

This document provides general guidance and is not legal advice. Consult with a legal professional familiar with privacy regulations in your jurisdiction.