/**
 * Mermaid diagram definitions for the Maternal Health Monitoring System documentation.
 * Each export is a { code, title } object.
 */

// 1. BPMN: Current Manual Process
export const bpmnCurrentProcess = {
  title: "BPMN Diagram of Current (Manual) Process",
  code: `flowchart TD
    A([Patient Arrives]) --> B[Walk-in to Health Center]
    B --> C[Fill out paper registration form]
    C --> D[Midwife manually records in logbook]
    D --> E{Registration Type?}
    E -->|Maternal| F[Prenatal Checkup - Manual logbook]
    E -->|Child| G[Immunization Schedule - Paper card]
    E -->|Child| H[Growth Monitoring - Paper chart]
    F --> I[Manual follow-up via personal visit or word-of-mouth]
    G --> I
    H --> I
    I --> J[Generate monthly reports manually by counting from logs]
    J --> K([End])

    style A fill:#f9f,stroke:#333
    style K fill:#f9f,stroke:#333
    style I fill:#fdd,stroke:#c00
    style J fill:#fdd,stroke:#c00`
};

// 2. BPMN: Proposed Automated Process
export const bpmnProposedProcess = {
  title: "BPMN Diagram of Proposed (Automated) Process",
  code: `flowchart TD
    A([Patient Arrives]) --> B[Digital Patient Registration via Web Form]
    B --> C[System auto-generates health records and vaccine schedule]
    C --> D{Registration Type?}
    D -->|Maternal| E[Maternal Checkup - Digital logging]
    D -->|Child| F[Immunization Tracking - Auto EPI Schedule]
    D -->|Child| G[Growth Monitoring - Digital charts]
    E --> H[Automated SMS Notifications and Reminders]
    F --> H
    G --> H
    H --> I[Patient Portal - View Records and Schedules]
    H --> J[Real-time Online Chat - Patient and Healthcare Worker]
    I --> K[Auto-generated Reports and Analytics]
    J --> K
    K --> L([End])

    style A fill:#9f9,stroke:#333
    style L fill:#9f9,stroke:#333
    style H fill:#dfd,stroke:#0a0
    style K fill:#dfd,stroke:#0a0`
};

// 3. MVC Architecture
export const mvcArchitecture = {
  title: "MVC Architecture",
  code: `flowchart LR
    subgraph Client
        Browser[Browser / Client]
    end
    subgraph Controller
        AC[AuthController]
        PC[PageController]
    end
    subgraph Model
        Patient
        MaternalRecord
        ChildRecord
        Immunization
        GrowthMeasurement
        SmsMessage
        ChatMessage
        User
    end
    subgraph View["View (Blade Templates)"]
        V1[dashboard]
        V2[records]
        V3[maternal]
        V4[immunization]
        V5[growth]
        V6[sms]
        V7[messaging]
        V8[reports]
        V9[patient portal]
    end
    subgraph Database
        DB[(MySQL / SQLite)]
    end

    Browser -- HTTP Request --> AC
    Browser -- HTTP Request --> PC
    AC --> User
    PC --> Patient
    PC --> MaternalRecord
    PC --> ChildRecord
    PC --> Immunization
    PC --> GrowthMeasurement
    PC --> SmsMessage
    PC --> ChatMessage
    User --> DB
    Patient --> DB
    MaternalRecord --> DB
    ChildRecord --> DB
    Immunization --> DB
    AC --> V1
    PC --> V2
    PC --> V3
    PC --> V4
    PC --> V5
    PC --> V6
    PC --> V7
    PC --> V8
    PC --> V9
    V1 -- HTML Response --> Browser
    V2 -- HTML Response --> Browser`
};

// 4. Offline/Online Architecture
export const offlineArchitecture = {
  title: "Offline/Online PWA Architecture",
  code: `flowchart TD
    subgraph Device["Tablet / Browser"]
        SW[Service Worker - Cache]
        IDB[IndexedDB - Local Database]
        BS[Background Sync]
    end
    subgraph Internet["Internet Connection"]
        CON{Online?}
    end
    subgraph Server["Laravel Server"]
        AUTH[Auth API - Sanctum]
        API[REST API Endpoints]
        MYSQL[(MySQL Database)]
    end

    SW --> CON
    IDB --> CON
    BS --> CON
    CON -->|Yes - Syncs| AUTH
    CON -->|Yes - Syncs| API
    CON -->|No - Queues locally| BS
    AUTH --> MYSQL
    API --> MYSQL

    style Device fill:#e3f2fd,stroke:#1565c0
    style Server fill:#e8f5e9,stroke:#2e7d32
    style CON fill:#fff9c4,stroke:#f9a825`
};

// 5. Use Case: Account Management
export const useCaseAccount = {
  title: "Use Case Diagram: Account Authorization and Authentication",
  code: `flowchart LR
    Admin(("Admin (Midwife)"))
    Patient(("User (Patient)"))
    
    subgraph System["Account Management System"]
        UC1[Register New Account]
        UC2[Disable Account]
        UC3[Update Account Info]
        UC4[Update Password]
        UC5[View Own Profile]
        UC6[Login with Role]
        UC7[Logout]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4
    Admin --> UC6
    Admin --> UC7
    Patient --> UC5
    Patient --> UC4
    Patient --> UC6
    Patient --> UC7`
};

// 6. Use Case: Patient Registration
export const useCasePatient = {
  title: "Use Case Diagram: Patient Registration Management",
  code: `flowchart LR
    Admin(("Admin (Midwife)"))
    
    subgraph System["Patient Registration System"]
        UC1[Register Maternal Patient]
        UC2[Register Child Patient]
        UC3[Search and Filter Patients]
        UC4[View Patient Records]
        UC5[Update Patient Status]
        UC6["Auto-Create MaternalRecord (LMP to EDD)"]
        UC7["Auto-Create ChildRecord + EPI Schedule (14 doses)"]
        UC8[Auto-Create Initial Growth Measurement]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4
    Admin --> UC5
    UC1 -.->|includes| UC6
    UC2 -.->|includes| UC7
    UC2 -.->|includes| UC8`
};

// 7. Use Case: Maternal Health
export const useCaseMaternal = {
  title: "Use Case Diagram: Maternal Health Monitoring",
  code: `flowchart LR
    Admin(("Admin (Midwife)"))
    Patient(("User (Patient)"))
    
    subgraph System["Maternal Health Monitoring"]
        UC1[Add Checkup Visit Log]
        UC2[View Checkup History]
        UC3[Track EDD / LMP]
        UC4[View Own Maternal Records]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Patient --> UC4
    Patient --> UC2`
};

// 8. Use Case: Child Health
export const useCaseChild = {
  title: "Use Case Diagram: Child Health Monitoring",
  code: `flowchart LR
    Admin(("Admin (Midwife)"))
    Patient(("User (Patient)"))
    
    subgraph System["Child Health Monitoring"]
        UC1[Mark Vaccine as Administered]
        UC2[Add Growth Measurement]
        UC3[View Immunization Schedule]
        UC4[View Growth Chart]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4
    Patient --> UC3
    Patient --> UC4`
};

// 9. Use Case: SMS
export const useCaseSms = {
  title: "Use Case Diagram: SMS Notifications Management",
  code: `flowchart LR
    Admin(("Admin (Midwife)"))
    
    subgraph System["SMS Management System"]
        UC1[Configure SMS Gateway]
        UC2[Test Gateway Connection]
        UC3[Send Manual SMS to Patient]
        UC4[View SMS History Log]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4`
};

// 10. Use Case: Messaging
export const useCaseMessaging = {
  title: "Use Case Diagram: Online Messaging",
  code: `flowchart LR
    Admin(("Admin (Midwife)"))
    Patient(("User (Patient)"))
    
    subgraph System["Online Messaging System"]
        UC1[View Patient User List]
        UC2[Select Active Chat]
        UC3[Send Message with Attachments]
        UC4[View Read Receipts]
        UC5[Chat with Midwife]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4
    Patient --> UC5
    Patient --> UC3
    Patient --> UC4`
};

// 11. Use Case: Reports
export const useCaseReports = {
  title: "Use Case Diagram: Generate Reports",
  code: `flowchart LR
    Admin(("Admin (Midwife)"))
    
    subgraph System["Reports and Analytics"]
        UC1[View Dashboard KPIs]
        UC2[View Monthly Registration Trends]
        UC3[View Immunization Compliance Rate]
        UC4[Filter by Time Period]
    end

    Admin --> UC1
    Admin --> UC2
    Admin --> UC3
    Admin --> UC4`
};

// 12. Entity Relationship Diagram
export const entityRelationship = {
  title: "TrackERB-Style Entity Relationship Diagram",
  code: `erDiagram
    USERS {
        bigint id PK
        string name
        string email UK
        string password
        string role
        timestamp email_verified_at
        string remember_token
        timestamp created_at
        timestamp updated_at
    }
    PATIENTS {
        bigint id PK
        bigint user_id FK
        string first_name
        string last_name
        date dob
        string gender
        string phone
        string email
        text address
        string barangay
        string occupation
        string emergency_contact_name
        string emergency_contact_phone
        string registration_type
        string status
        timestamp created_at
        timestamp updated_at
    }
    MATERNAL_RECORDS {
        bigint id PK
        bigint patient_id FK
        date lmp
        date edd
        int gravida
        int para
        int abortions
        int still_births
        string philhealth_number
        string blood_type
        float height_cm
        text allergies
        json medical_history
        json birth_plan
        timestamp created_at
        timestamp updated_at
    }
    MATERNAL_CHECKUPS {
        bigint id PK
        bigint maternal_record_id FK
        int visit_number
        date date
        float weight_kg
        string bp
        string age_of_gestation
        int fetal_heart_rate
        string attendant
        string status
        text notes
        date next_visit_date
        timestamp created_at
        timestamp updated_at
    }
    CHILD_RECORDS {
        bigint id PK
        bigint patient_id FK
        float birth_weight_kg
        float birth_height_cm
        string birth_type
        string delivery_type
        timestamp created_at
        timestamp updated_at
    }
    IMMUNIZATIONS {
        bigint id PK
        bigint child_record_id FK
        string vaccine_name
        int dose_number
        date scheduled_date
        string status
        date given_date
        string administered_by
        text remarks
        timestamp created_at
        timestamp updated_at
    }
    GROWTH_MEASUREMENTS {
        bigint id PK
        bigint child_record_id FK
        date date
        int age_months
        float weight_kg
        float height_cm
        string status
        timestamp created_at
        timestamp updated_at
    }
    SMS_MESSAGES {
        bigint id PK
        bigint patient_id FK
        string phone_number
        text message
        string status
        timestamp sent_at
        string type
        timestamp created_at
        timestamp updated_at
    }
    CHAT_MESSAGES {
        bigint id PK
        bigint sender_id FK
        bigint receiver_id FK
        text message
        boolean is_read
        string attachment_path
        string attachment_name
        string attachment_type
        timestamp created_at
        timestamp updated_at
    }

    USERS ||--o{ PATIENTS : "has"
    USERS ||--o{ CHAT_MESSAGES : "sends"
    USERS ||--o{ CHAT_MESSAGES : "receives"
    PATIENTS ||--o| MATERNAL_RECORDS : "has"
    PATIENTS ||--o| CHILD_RECORDS : "has"
    PATIENTS ||--o{ SMS_MESSAGES : "receives"
    MATERNAL_RECORDS ||--o{ MATERNAL_CHECKUPS : "has"
    CHILD_RECORDS ||--o{ IMMUNIZATIONS : "has"
    CHILD_RECORDS ||--o{ GROWTH_MEASUREMENTS : "has"`
};

// 13. Activity: Register User
export const actRegisterUser = {
  title: "Activity Diagram: Register and Create New User",
  code: `flowchart TD
    S([Start]) --> A[Admin navigates to Admin Panel]
    A --> B["Click 'Register New User'"]
    B --> C[Enter User Info: Name, Email, Password, Role]
    C --> D{Validate Input Data?}
    D -->|Valid| E[Create User Account]
    D -->|Invalid| F[Show Error Messages]
    F --> C
    E --> G[Hash Password and Store in DB]
    G --> H[Redirect with Success Message]
    H --> Z([End])

    style S fill:#4CAF50,color:#fff
    style Z fill:#f44336,color:#fff`
};

// 14. Activity: Login/Logout
export const actLoginLogout = {
  title: "Activity Diagram: Login and Logout",
  code: `flowchart TD
    S([Start]) --> A["Navigate to Login Page (/)"]
    A --> B[Enter Email, Password, Select Role]
    B --> C{Validate Credentials + Role Match?}
    C -->|Valid| D[Regenerate Session]
    C -->|Invalid| E["Show Error: Credentials do not match"]
    E --> B
    D --> F{User Role?}
    F -->|Admin| G[Redirect to /dashboard]
    F -->|Patient| H[Redirect to /portal]
    G --> I([Logged In])
    H --> I

    I --> J["Click 'Logout'"]
    J --> K[Invalidate Session + Regenerate CSRF Token]
    K --> L["Redirect to / (Login Page)"]
    L --> Z([End])

    style S fill:#4CAF50,color:#fff
    style Z fill:#f44336,color:#fff
    style I fill:#2196F3,color:#fff`
};

// 15. Activity: Patient Registration
export const actPatientRegistration = {
  title: "Activity Diagram: Patient Registration Process",
  code: `flowchart TD
    S([Start]) --> A[Navigate to /register]
    A --> B[Fill Registration Form]
    B --> C{Validate Required Fields?}
    C -->|Invalid| D[Show Validation Errors]
    D --> B
    C -->|Valid| E{Email Provided?}
    E -->|Yes| F[Create Patient User Account]
    E -->|No| G[Skip User Creation]
    F --> H[Save Patient Profile to DB]
    G --> H
    H --> I{Registration Type?}
    I -->|Maternal| J["Create MaternalRecord (LMP → EDD)"]
    I -->|Child| K[Create ChildRecord + Initial Growth]
    K --> L[Generate 14-dose EPI Vaccine Schedule]
    J --> M[Redirect to /records with Success]
    L --> M
    M --> Z([End])

    style S fill:#4CAF50,color:#fff
    style Z fill:#f44336,color:#fff`
};

// 16. Activity: Maternal Checkup
export const actMaternalCheckup = {
  title: "Activity Diagram: Maternal Checkup Process",
  code: `flowchart TD
    S([Start]) --> A["Select Maternal Patient (?id=X)"]
    A --> B[Load Patient + Maternal Record + Checkup History]
    B --> C[Enter Checkup: Weight, BP, Fetal Heart Rate, Notes]
    C --> D{Validate Input?}
    D -->|Invalid| E[Show Errors]
    E --> C
    D -->|Valid| F["Auto-calculate Visit Number, Gestational Age, Next Visit (+4 weeks)"]
    F --> G[Save Checkup to Database]
    G --> H[Refresh with Success Message]
    H --> Z([End])

    style S fill:#4CAF50,color:#fff
    style Z fill:#f44336,color:#fff`
};

// 17. Activity: Immunization
export const actImmunization = {
  title: "Activity Diagram: Immunization Management Process",
  code: `flowchart TD
    S([Start]) --> A[Navigate to Immunization Page]
    A --> B[Load Child Record + Immunization Schedule]
    B --> C[Select Vaccine Dose to Mark as Administered]
    C --> D[Submit with Optional Remarks]
    D --> E["Update Status to 'Given'"]
    E --> F[Record Date and Administrator Name]
    F --> G["Refresh with Success: 'Dose Administered'"]
    G --> Z([End])

    style S fill:#4CAF50,color:#fff
    style Z fill:#f44336,color:#fff`
};

// 18. Activity: SMS Notification
export const actSmsNotification = {
  title: "Activity Diagram: SMS Notification Process",
  code: `flowchart TD
    S([Start]) --> A["Navigate to SMS Page (/sms)"]
    A --> B[Load SMS Log + Patient List + Gateway Status]
    B --> C["Select Patient + Type Message + Click 'Send'"]
    C --> D["Format Phone to E.164 (+63xxxxxxxxxx)"]
    D --> E[Send via Android SMS Gateway API]
    E --> F{Sent Successfully?}
    F -->|Yes| G["Log Status: 'Sent' + Show Success"]
    F -->|No| H["Log Status: 'Failed' + Show Error"]
    G --> Z([End])
    H --> Z

    style S fill:#4CAF50,color:#fff
    style Z fill:#f44336,color:#fff`
};

export const dataCollectionProcess = {
  title: "Data Collection Process",
  code: `flowchart TD
    A([Start]) --> B[1. Identify Target Respondents]
    B --> B1[Health Center Staff: Midwives, Admin]
    B --> B2[Patient Users: Mothers, Guardians]
    B1 --> C[2. Conduct Orientation Sessions]
    B2 --> C
    C --> C1[Introduce system features and purpose]
    C1 --> D[3. Distribute Digital Survey - Google Forms]
    D --> D1[System Usability Scale - 20 statements]
    D1 --> E[4. Collect Responses]
    E --> E1[7 Healthcare Worker responses]
    E --> E2[10 Patient User responses]
    E1 --> F[5. Analyze Using Descriptive Statistics]
    E2 --> F
    F --> F1[Generate summary graphs]
    F1 --> G[6. Apply Feedback to System Improvements]
    G --> H([End])
    
    style A fill:#4CAF50,color:#fff
    style H fill:#f44336,color:#fff`
};

export const allDiagrams = [
  dataCollectionProcess,
  bpmnCurrentProcess,
  bpmnProposedProcess,
  mvcArchitecture,
  offlineArchitecture,
  useCaseAccount,
  useCasePatient,
  useCaseMaternal,
  useCaseChild,
  useCaseSms,
  useCaseMessaging,
  useCaseReports,
  entityRelationship,
  actRegisterUser,
  actLoginLogout,
  actPatientRegistration,
  actMaternalCheckup,
  actImmunization,
  actSmsNotification,
];
