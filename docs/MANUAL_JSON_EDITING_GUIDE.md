# Manual JSON Editing Guide for Soft Skill Competencies

This guide explains how to manually add soft skill competencies to the JSON file and what happens when you do.

## 📍 File Location
```
config/soft_skill_levels.json
```

## 🔑 How the System Finds Competencies

When you click "View Levels" for a competency, the system:
1. Takes the competency name (e.g., "Team Leadership")
2. Converts it to lowercase and replaces spaces with underscores: "team_leadership"
3. Looks for this key in the JSON file

## 📝 Required JSON Structure

```json
{
  "soft_skills": {
    "competency_key": {
      "name": "Display Name",
      "definition": "Clear definition of the competency",
      "description": "Detailed description of what this competency involves",
      "levels": {
        "1": {
          "title": "Level 1 Title",
          "behaviors": [
            "First specific behavior",
            "Second specific behavior", 
            "Third specific behavior",
            "Fourth specific behavior"
          ]
        },
        "2": {
          "title": "Level 2 Title",
          "behaviors": [
            "First specific behavior",
            "Second specific behavior",
            "Third specific behavior", 
            "Fourth specific behavior"
          ]
        },
        "3": {
          "title": "Level 3 Title",
          "behaviors": [
            "First specific behavior",
            "Second specific behavior",
            "Third specific behavior",
            "Fourth specific behavior"
          ]
        },
        "4": {
          "title": "Level 4 Title",
          "behaviors": [
            "First specific behavior",
            "Second specific behavior",
            "Third specific behavior",
            "Fourth specific behavior"
          ]
        }
      }
    }
  },
  "level_mapping": {
    "1": "basic",
    "2": "intermediate", 
    "3": "advanced",
    "4": "expert"
  }
}
```

## 📋 Complete Example: Adding "Communication Skills"

### Step 1: Determine the Key
- Competency Name: "Communication Skills"
- JSON Key: "communication_skills"

### Step 2: Add to JSON File
```json
{
  "soft_skills": {
    "people_management": {
      // ... existing people_management data ...
    },
    "communication_skills": {
      "name": "Communication Skills",
      "definition": "Does the person effectively convey information and ideas to others?",
      "description": "Communication involves the ability to clearly express thoughts, ideas, and information to others through various channels. It includes listening skills, non-verbal communication, and adapting the message to the audience.",
      "levels": {
        "1": {
          "title": "Conveys basic information",
          "behaviors": [
            "Shares basic information clearly and directly",
            "Listens attentively when others speak",
            "Uses appropriate language for the situation",
            "Asks questions to ensure understanding"
          ]
        },
        "2": {
          "title": "Adapts communication to audience",
          "behaviors": [
            "Adjusts communication style based on audience",
            "Presents information in a structured way",
            "Handles questions and feedback effectively",
            "Uses non-verbal cues to support message"
          ]
        },
        "3": {
          "title": "Influences through communication",
          "behaviors": [
            "Persuades others to accept ideas or proposals",
            "Communicates complex concepts clearly",
            "Manages difficult conversations constructively",
            "Builds rapport through effective communication"
          ]
        },
        "4": {
          "title": "Masters strategic communication",
          "behaviors": [
            "Develops and implements communication strategies",
            "Represents the organization effectively to stakeholders",
            "Manages communication crises with confidence",
            "Mentors others in improving communication skills"
          ]
        }
      }
    }
  },
  "level_mapping": {
    "1": "basic",
    "2": "intermediate",
    "3": "advanced", 
    "4": "expert"
  }
}
```

## ⚠️ Important Rules

### 1. **Key Naming Convention**
- Use lowercase
- Replace spaces with underscores
- Remove special characters
- Examples:
  - "Team Leadership" → "team_leadership"
  - "Problem Solving" → "problem_solving"
  - "Time Management" → "time_management"

### 2. **Required Fields**
- `name`: Display name (can have spaces and capital letters)
- `definition`: Short definition (one sentence)
- `description`: Detailed description (paragraph)
- `levels`: Object with 4 levels (1, 2, 3, 4)
- Each level needs:
  - `title`: Level title
  - `behaviors`: Array of exactly 4 behaviors

### 3. **Behaviors Array**
- Must have exactly 4 behaviors
- Each behavior should be a complete sentence
- Behaviors should be observable and measurable
- Use consistent language style across all levels

## ✅ What Happens When You Add Manually

### ✅ Works Automatically:
1. **UI Recognition**: The system will find and display your competency
2. **Loading**: All fields populate correctly in the modal
3. **Markdown Conversion**: Behaviors appear as bullet points in text areas
4. **Saving**: You can edit and save changes through the UI
5. **Level Mapping**: Levels 1-4 map to basic/intermediate/advanced/expert

### ⚠️ Potential Issues:
1. **Key Mismatch**: If the key doesn't match the competency name pattern, UI won't find it
2. **JSON Syntax**: Any syntax error breaks the entire system
3. **Missing Fields**: Incomplete structure causes loading errors
4. **File Permissions**: File must be writable (666) for UI saves to work

## 🛠️ Best Practices

### 1. **Backup First**
```bash
cp config/soft_skill_levels.json config/soft_skill_levels.json.backup
```

### 2. **Use JSON Validator**
Validate your JSON after editing using tools like:
- [JSONLint](https://jsonlint.com/)
- [Online JSON Validator](https://jsonformatter.curiousconcept.com/)

### 3. **Test Incrementally**
Add one competency at a time and test in the UI before adding more.

### 4. **Follow Existing Patterns**
Copy the structure from existing competencies to ensure consistency.

## 🔍 Troubleshooting

### Issue: "Competency not found in UI"
**Solution**: Check that the JSON key matches the competency name pattern

### Issue: "Error loading soft skill levels"
**Solution**: Validate JSON syntax and ensure all required fields are present

### Issue: "Changes not saving"
**Solution**: Check file permissions (should be 666)

### Issue: "Behaviors not displaying correctly"
**Solution**: Ensure behaviors array has exactly 4 items

## 📝 Quick Template

Copy and paste this template, then fill in your details:

```json
"your_competency_key": {
  "name": "Your Competency Name",
  "definition": "Does the person [competency definition]?",
  "description": "[Detailed description of the competency]",
  "levels": {
    "1": {
      "title": "[Level 1 title]",
      "behaviors": [
        "[Behavior 1]",
        "[Behavior 2]",
        "[Behavior 3]",
        "[Behavior 4]"
      ]
    },
    "2": {
      "title": "[Level 2 title]",
      "behaviors": [
        "[Behavior 1]",
        "[Behavior 2]",
        "[Behavior 3]",
        "[Behavior 4]"
      ]
    },
    "3": {
      "title": "[Level 3 title]",
      "behaviors": [
        "[Behavior 1]",
        "[Behavior 2]",
        "[Behavior 3]",
        "[Behavior 4]"
      ]
    },
    "4": {
      "title": "[Level 4 title]",
      "behaviors": [
        "[Behavior 1]",
        "[Behavior 2]",
        "[Behavior 3]",
        "[Behavior 4]"
      ]
    }
  }
}
```

Remember to add a comma after the previous competency if you're adding to an existing file!