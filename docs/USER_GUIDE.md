# 📖 Agora – User Guide (v1.5.0)

## 🔹 Overview

Agora is a **participatory democracy app for Nextcloud**.  
It allows communities and organizations to **create, support, and debate** contributions called _inquiries_.  

Agora ensures **transparency, inclusion, and democratic participation**.

---

## 🔹 Families & Inquiry Types

- Each inquiry belongs to a **family**, representing a theme or organizational area.  
- **Inquiry types and fields are fully configurable** in **Admin Settings**: creation, editing, response rules, and transformation rules.  
- **Only authorized group members** can modify families and types:  
  - **Legislative menu** → `Agora Legislative` group.  
  - **Administrative menu** → `Agora Administrative` group.  
  - **Collective menu** → `Agora Collective` group.  
  - Other groups can only access menus/families assigned to them.

---

## 🔹 Moderation System

Agora includes a **configurable moderation system**, which can be enabled or disabled in **Admin Settings**.

- **Use Moderation** → toggles moderation on/off.  
  - If disabled: all inquiries are automatically accepted.  
  - If enabled: a **“To Moderate”** menu appears for review.  
- **Moderators** (`Agora Moderator`) can:  
  - Approve, reject, or archive an inquiry.  
  - Change moderation status.  
- **Officials** (`Agora Official`) can:  
  - Post official responses.  
  - Bypass moderation if configured.

---

## 🔹 Permissions and Groups

Agora has **six main groups**:

| Group | Role |
|-------|------|
| `Agora Users` | Create, support, and comment on inquiries within their families. Mandatory to see attached files and inquries covers|
| `Agora Moderator` | Moderate inquiries (if moderation is enabled). |
| `Agora Official` | Publish official responses and may bypass moderation. |
| `Agora Legislative` | Access to legislative menu/families. Can modify families and types in legislative scope. |
| `Agora Administrative` | Access to administrative menu/families. Can modify families and types in administrative scope. |
| `Agora Collective` | Access to collective families. Can manage collective inquiries. |

📂 **Attachments and private inquiries**: visible only to groups with access to the corresponding families.

---

## 🔹 Administration

Administrators can configure all app features via **Admin Settings**:

- **Locations** → cities, districts, zones.  
- **Categories** → thematic classification (e.g., environment, education).  
- **Families** → create/edit families, assign allowed groups.  
- **Inquiry Types** → define types, fields, response rules, and transformations.  
- **Moderation Settings** → enable/disable moderation and assign groups.  
- **Permissions** → control access by family or menu for each group.

---

## 🔹 Usage

1. **Open Agora** from the Nextcloud app menu.  
2. **Create an inquiry**:  
   - Click _New Inquiry_.  
   - Choose the user or group user to oepn.  
   - Fill in title, description, category, location.  
   - Add files and cover if needed.  
   - Publish (moderation applies if enabled).  
3. **Interact with inquiries**:  
   - Comment.  
   - Support.  
   - Create a **child inquiry** if allowed by the family/type.  
4. **Moderate** (if member of `Agora Moderator` or `Agora Official`):  
   - Approve or reject an inquiry.  
   - Archive or delete inquiries.  
5. **Legislative/Administrative/Collective administrators**:  
   - Access menus and features specific to their group.  
   - Edit families and inquiry types assigned to their menu.

---

## 🔹 Features

- 🧱 **New database architecture**  
- 🎨 **Improved UX landing page**  
- 🆔 **Cover ID for each inquiry**  
- 🧩 **Configurable families and inquiry types**  
- 💬 **Comments and discussions**  
- 👍 **Supports/votes for inquiries**  
- 🔄 **Configurable transformations and responses**  
- 📎 **File attachments**  
- 🗃️ **Archiving & Soft Delete**  
- 🔒 **Data remains private on your Nextcloud server**  

---

## 🔹 Practical Examples

| Role | Example |
|------|---------|
| **User** | Creates a petition “Tree Planting” and receives supports. |
| **Moderator** | Reviews a debate or archives an old inquiry. |
| **Official** | Posts an official response to a legislative inquiry. |
| **Legislative / Administrative / Collective** | Manage families and inquiry types assigned to their menu. |

---

## 🔹 Roadmap

- Integration with **Forms**, **Deck**, **Polls**, **Cospend**.  
- Structured debates.  
- Law project modules: article comments and supports.  
- Collectives → Polls for consultation/referendum.  
- Advanced quorums & workflows.  
- Improved permissions and visibility control.

