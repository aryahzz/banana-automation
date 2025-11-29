# ChatGPT Agent Instructions  
For: Pronamic GravityForms Repository  
Repository: https://github.com/pronamic/gravityforms

---

# 🧠 Purpose of This Document
This file instructs the AI agent (ChatGPT / Codex) on **how to generate code that remains fully synchronized with this repository**, respecting its architecture, file structure, coding style, and logic.

Whenever you (the agent) produce new code, ALWAYS assume:

➡️ You are working *inside this repo*.  
➡️ All new logic must adapt to, extend, or integrate with existing code.  
➡️ No code should contradict or duplicate existing structure.

---

# 🏛️ High-Level Rules

### ✔ Always follow the repository’s architecture  
Use the same directory patterns, naming conventions, and file organization.

### ✔ Always follow the repository’s coding conventions  
Match:  
- indentation  
- function naming  
- class naming  
- docblocks  
- WordPress & Gravity Forms standards  

### ✔ Always reuse existing logic when possible  
Before generating new helpers, classes, or rendering logic:  
➡️ Search for similar patterns in the repo  
➡️ Extend instead of reinventing  

### ✔ Never introduce dependencies not used in repo  
Unless explicitly requested.

### ✔ Every generated code block must specify its file path  
Example:  
`File: includes/class-example.php`  
Then code block.

---

# 📁 Repository File Map (Critical Section)

Below is the **complete map of important files and directories**, and their responsibilities.

You MUST use this map to decide **where new logic belongs**.

---

## 📌 Root-Level PHP Files

### `gravityforms.php`
Main plugin bootstrap.  
Loads required components, registers hooks, and initializes the plugin.

Use for:  
- High-level initialization  
- Registering new global hooks  
- Plugin-wide behavior

---

### `forms_model.php`
Core data layer.  
Handles CRUD for forms, entries, metadata, and DB operations.

Use for:  
- Querying forms or entries  
- Database interactions  
- Adding new model-level helper functions  

---

### `form_display.php`
Responsible for front-end form rendering.

Use for:  
- Custom HTML rendering  
- Field structure modifications  
- Display hooks  
- Conditional logic rendering  

---

### `form_detail.php`
Admin-side **form editor** screen.

Use for:  
- Field editor UI  
- Drag/drop behavior  
- Advanced settings behavior  
- Form configuration panels  

---

### `entry_detail.php`
Shows **single entry details** in admin.

Use for:  
- Displaying data for a single submitted entry  
- Enhancing the entry view  
- Adding new entry actions or meta panels  

---

### `entry_list.php`
Admin list of entries (List Table).

Use for:  
- Entry sorting  
- Filtering  
- Bulk actions  
- Customizing list columns  

---

### `form_list.php`
Admin list of forms.

Use for:  
- Form sorting / searching  
- Adding new list columns  
- Custom views  

---

### `notification.php`
Handles notifications (email, webhook, etc.)

Use for:  
- Adding new notification channels  
- Extending email logic  
- New notification merge tags  

---

### `settings.php`
Main plugin settings page.

Use for:  
- Global plugin options  
- Adding whole new settings sections  

---

### `help.php`
Contains admin help screens & contextual help.

---

### `preview.php`
Displays form preview inside admin.

---

### `print-entry.php`
Generates the printable entry view.

---

### `tooltips.php`
Defines admin tooltips used across UI.

---

### `widget.php`
Gravity Forms WordPress widget.

---

### `js.php`
Dynamically generates JavaScript for some admin screens.

---

### `xml.php`
Handles XML import/export for forms.

---

# 📁 Directories

## `/includes/`
Core backend PHP code.  
Most new functionality should be placed here.

Contains:  
- helper classes  
- integrations  
- service classes  
- backend logic  

**USE THIS DIRECTORY for most new backend features.**

---

## `/legacy/`
Legacy code retained for backward compatibility.

Do NOT add new code here unless extending legacy behavior.

---

## `/js/`
JavaScript files for admin and front-end.

Use for:  
- form editor JS  
- entry list JS  
- field settings JS  
- conditional logic UI scripts  

---

## `/assets/`
Static assets:  
- CSS  
- images  
- fonts  
- SVG  
- admin styles  

---

## `/languages/`
Translations: `.po`, `.mo`.

---

## `/css/`, `/images/`, `/fonts/`
Older or static assets.  
Try to prefer `/assets/` unless continuation of older logic.

---

# 🔍 Choosing the Correct File (Decision Guide)

When generating new code, follow this routing table:

| Feature Type | File / Directory |
|--------------|------------------|
| Rendering form fields | `form_display.php` |
| Editing form fields (admin) | `form_detail.php` |
| Showing single entry | `entry_detail.php` |
| Listing entries | `entry_list.php` |
| Listing forms | `form_list.php` |
| Notifications | `notification.php` |
| Plugin settings | `settings.php` |
| Print entry view | `print-entry.php` |
| XML Import/Export | `xml.php` |
| Widgets | `widget.php` |
| Backend logic / helpers | `/includes/` |
| Admin JS | `/js/` |
| Front-end JS | `/js/` |
| Assets | `/assets/` |
| Legacy behavior | `/legacy/` |

---

# 🧩 How to Extend the Plugin Properly

When generating code:

### 1. ALWAYS identify:  
**“Which file(s) does this feature belong to?”**  
Use the file map above.

---

### 2. ALWAYS integrate with WordPress and Gravity Forms hooks
Use actions such as:

- `gform_pre_submission`
- `gform_after_submission`
- `gform_pre_render`
- `gform_admin_pre_render`
- `gform_entry_detail_content`
- etc.

Follow patterns already used in codebase.

---

### 3. ALWAYS match style + architecture
Example conventions:
- function names: `gf_function_name`
- class names: `GF_Class_Name`
- WordPress-style docblocks  
- snake_case for array keys  
- strict checking where relevant  

---

# 🛠️ File Creation Rules

When generating a new file:

You MUST include:

- **Full path** (relative to repo root)  
- **Purpose description**  
- **Integration explanation** (hooks, classes, dependencies)

Example:

