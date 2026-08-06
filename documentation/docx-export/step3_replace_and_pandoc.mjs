import fs from 'fs';

const sourceMd = fs.readFileSync('/app/SoftwareDocumentation.md', 'utf-8');
let newMd = sourceMd;

console.log('Injecting diagrams into markdown...');

const replacements = [
  { match: /### 1\.2 Data Collection and Analysis Process[\s\S]*?```[\s\S]*?```/, replace: "### 1.2 Data Collection and Analysis Process\n\n![Data Collection Process](docx-export/images/diagram_0.png)\n\nThe use of digital forms in collecting survey responses was done to minimize the cost of using pen and paper and streamline the analysis of the collected data. The results of the Usability Test were used to develop the application further." },
  { match: /### 2\.1 Current \(Manual\) Process[\s\S]*?```[\s\S]*?```/, replace: "### 2.1 Current (Manual) Process\n\n![Current Process](docx-export/images/diagram_1.png)" },
  { match: /### 2\.2 Proposed \(Automated\) Process[\s\S]*?```[\s\S]*?```/, replace: "### 2.2 Proposed (Automated) Process\n\n![Proposed Process](docx-export/images/diagram_2.png)" },
  { match: /### 3\.1 Laravel and the MVC Architecture[\s\S]*?```[\s\S]*?```/, replace: "### 3.1 Laravel and the MVC Architecture\n\n![MVC Architecture](docx-export/images/diagram_3.png)\n\nThe system was developed using **Laravel 13.x**..." },
  { match: /### 3\.3 Offline\/Online Architecture[\s\S]*?```[\s\S]*?```/, replace: "### 3.3 Offline/Online Architecture\n\n![Offline Architecture](docx-export/images/diagram_4.png)\n\nThe system is designed to work reliably in areas with intermittent internet connectivity..." },
  { match: /### 4\.1 Account Authorization and Authentication[\s\S]*?```[\s\S]*?```/, replace: "### 4.1 Account Authorization and Authentication\n\n![Account Management](docx-export/images/diagram_5.png)" },
  { match: /### 4\.2 Patient Registration Management[\s\S]*?```[\s\S]*?```/, replace: "### 4.2 Patient Registration Management\n\n![Patient Registration](docx-export/images/diagram_6.png)" },
  { match: /### 4\.3 Maternal Health Monitoring[\s\S]*?```[\s\S]*?```/, replace: "### 4.3 Maternal Health Monitoring\n\n![Maternal Health](docx-export/images/diagram_7.png)" },
  { match: /### 4\.4 Child Health Monitoring[\s\S]*?```[\s\S]*?```/, replace: "### 4.4 Child Health Monitoring\n\n![Child Health](docx-export/images/diagram_8.png)" },
  { match: /### 4\.5 SMS Notifications Management[\s\S]*?```[\s\S]*?```/, replace: "### 4.5 SMS Notifications Management\n\n![SMS Notifications](docx-export/images/diagram_9.png)" },
  { match: /### 4\.6 Online Messaging[\s\S]*?```[\s\S]*?```/, replace: "### 4.6 Online Messaging\n\n![Online Messaging](docx-export/images/diagram_10.png)" },
  { match: /### 4\.7 Generate Reports[\s\S]*?```[\s\S]*?```/, replace: "### 4.7 Generate Reports\n\n![Generate Reports](docx-export/images/diagram_11.png)" },
  { match: /## 5\. Entity Relationship Diagram[\s\S]*?```[\s\S]*?```/, replace: "## 5. Entity Relationship Diagram\n\nThe Entity Relationship Diagram shows the relationship, cardinalities, associations, and data types of the entities used by the system.\n\n![ER Diagram](docx-export/images/diagram_12.png)" },
  { match: /### 6\.1 Register & Create New User[\s\S]*?```[\s\S]*?```/, replace: "### 6.1 Register & Create New User\n\n![Register User](docx-export/images/diagram_13.png)" },
  { match: /### 6\.2 Login and Logout[\s\S]*?```[\s\S]*?```/, replace: "### 6.2 Login and Logout\n\n![Login/Logout](docx-export/images/diagram_14.png)" },
  { match: /### 6\.3 Patient Registration Process[\s\S]*?```[\s\S]*?```/, replace: "### 6.3 Patient Registration Process\n\n![Patient Registration Process](docx-export/images/diagram_15.png)" },
  { match: /### 6\.4 Maternal Checkup Process[\s\S]*?```[\s\S]*?```/, replace: "### 6.4 Maternal Checkup Process\n\n![Maternal Checkup Process](docx-export/images/diagram_16.png)" },
  { match: /### 6\.5 Immunization Management Process[\s\S]*?```[\s\S]*?```/, replace: "### 6.5 Immunization Management Process\n\n![Immunization Process](docx-export/images/diagram_17.png)" },
  { match: /### 6\.6 SMS Notification Process[\s\S]*?```[\s\S]*?```/, replace: "### 6.6 SMS Notification Process\n\n![SMS Notification Process](docx-export/images/diagram_18.png)" }
];

replacements.forEach(r => {
  newMd = newMd.replace(r.match, r.replace);
});

fs.writeFileSync('/app/SoftwareDocumentationWithImages.md', newMd);
console.log('Saved SoftwareDocumentationWithImages.md');
