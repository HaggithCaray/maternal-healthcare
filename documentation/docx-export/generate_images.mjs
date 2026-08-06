import fs from 'fs';
import { execSync } from 'child_process';
import { allDiagrams } from './diagrams.mjs';

const sourceMd = fs.readFileSync('../SoftwareDocumentation.md', 'utf-8');
let newMd = sourceMd;

console.log('Generating mermaid images...');

if (!fs.existsSync('images')) {
  fs.mkdirSync('images');
}

allDiagrams.forEach((diagram, index) => {
  const mmdPath = `images/diagram_${index}.mmd`;
  
  // Write .mmd
  fs.writeFileSync(mmdPath, diagram.code);
});

// Run minlag/mermaid-cli docker for each file to ensure dependencies are isolated
for (let index = 0; index < allDiagrams.length; index++) {
    const pngPath = `images/diagram_${index}.png`;
    console.log(`Rendering diagram_${index}.png...`);
    try {
        // Use the official mermaid-cli docker image which has chromium built-in
        execSync(`docker run --rm -v "$(pwd):/data" minlag/mermaid-cli -i /data/images/diagram_${index}.mmd -o /data/images/diagram_${index}.png`, { stdio: 'inherit' });
    } catch (err) {
        console.error(`Failed to generate ${pngPath}`, err.message);
    }
}

console.log('Injecting diagrams into markdown...');

// 1. Current Process
newMd = newMd.replace(/### 2\.1 Current \(Manual\) Process[\s\S]*?```[\s\S]*?```/, 
  "### 2.1 Current (Manual) Process\n\n![Current Process](images/diagram_0.png)");

// 2. Proposed Process
newMd = newMd.replace(/### 2\.2 Proposed \(Automated\) Process[\s\S]*?```[\s\S]*?```/, 
  "### 2.2 Proposed (Automated) Process\n\n![Proposed Process](images/diagram_1.png)");

// 3. MVC
newMd = newMd.replace(/### 3\.1 Laravel and the MVC Architecture[\s\S]*?```[\s\S]*?```/, 
  "### 3.1 Laravel and the MVC Architecture\n\n![MVC Architecture](images/diagram_2.png)\n\nThe system was developed using **Laravel 13.x**...");

// 4. Offline
newMd = newMd.replace(/### 3\.3 Offline\/Online Architecture[\s\S]*?```[\s\S]*?```/, 
  "### 3.3 Offline/Online Architecture\n\n![Offline Architecture](images/diagram_3.png)\n\nThe system is designed to work reliably in areas with intermittent internet connectivity...");

// 5. Use Case: Account
newMd = newMd.replace(/### 4\.1 Account Authorization and Authentication[\s\S]*?```[\s\S]*?```/, 
  "### 4.1 Account Authorization and Authentication\n\n![Account Management](images/diagram_4.png)");

// 6. Use Case: Patient Registration
newMd = newMd.replace(/### 4\.2 Patient Registration Management[\s\S]*?```[\s\S]*?```/, 
  "### 4.2 Patient Registration Management\n\n![Patient Registration](images/diagram_5.png)");

// 7. Use Case: Maternal Health
newMd = newMd.replace(/### 4\.3 Maternal Health Monitoring[\s\S]*?```[\s\S]*?```/, 
  "### 4.3 Maternal Health Monitoring\n\n![Maternal Health](images/diagram_6.png)");

// 8. Use Case: Child Health
newMd = newMd.replace(/### 4\.4 Child Health Monitoring[\s\S]*?```[\s\S]*?```/, 
  "### 4.4 Child Health Monitoring\n\n![Child Health](images/diagram_7.png)");

// 9. Use Case: SMS
newMd = newMd.replace(/### 4\.5 SMS Notifications Management[\s\S]*?```[\s\S]*?```/, 
  "### 4.5 SMS Notifications Management\n\n![SMS Notifications](images/diagram_8.png)");

// 10. Use Case: Messaging
newMd = newMd.replace(/### 4\.6 Online Messaging[\s\S]*?```[\s\S]*?```/, 
  "### 4.6 Online Messaging\n\n![Online Messaging](images/diagram_9.png)");

// 11. Use Case: Reports
newMd = newMd.replace(/### 4\.7 Generate Reports[\s\S]*?```[\s\S]*?```/, 
  "### 4.7 Generate Reports\n\n![Generate Reports](images/diagram_10.png)");

// 12. ERD
newMd = newMd.replace(/## 5\. Entity Relationship Diagram[\s\S]*?```[\s\S]*?```/, 
  "## 5. Entity Relationship Diagram\n\nThe Entity Relationship Diagram shows the relationship, cardinalities, associations, and data types of the entities used by the system.\n\n![ER Diagram](images/diagram_11.png)");

// 13. Activity: Register
newMd = newMd.replace(/### 6\.1 Register & Create New User[\s\S]*?```[\s\S]*?```/, 
  "### 6.1 Register & Create New User\n\n![Register User](images/diagram_12.png)");

// 14. Activity: Login/Logout
newMd = newMd.replace(/### 6\.2 Login and Logout[\s\S]*?```[\s\S]*?```/, 
  "### 6.2 Login and Logout\n\n![Login/Logout](images/diagram_13.png)");

// 15. Activity: Patient Registration
newMd = newMd.replace(/### 6\.3 Patient Registration Process[\s\S]*?```[\s\S]*?```/, 
  "### 6.3 Patient Registration Process\n\n![Patient Registration Process](images/diagram_14.png)");

// 16. Activity: Maternal Checkup
newMd = newMd.replace(/### 6\.4 Maternal Checkup Process[\s\S]*?```[\s\S]*?```/, 
  "### 6.4 Maternal Checkup Process\n\n![Maternal Checkup Process](images/diagram_15.png)");

// 17. Activity: Immunization
newMd = newMd.replace(/### 6\.5 Immunization Management Process[\s\S]*?```[\s\S]*?```/, 
  "### 6.5 Immunization Management Process\n\n![Immunization Process](images/diagram_16.png)");

// 18. Activity: SMS
newMd = newMd.replace(/### 6\.6 SMS Notification Process[\s\S]*?```[\s\S]*?```/, 
  "### 6.6 SMS Notification Process\n\n![SMS Notification Process](images/diagram_17.png)");

fs.writeFileSync('SoftwareDocumentationWithImages.md', newMd);
console.log('Saved SoftwareDocumentationWithImages.md');
