const courses = {
  program_name:
    "Empowering 1 Million Youth Through Industry-Driven Digital Skills Training and Certification",
  courses: [
    {
      category: "Cybersecurity",
      jobs: [
        {
          no: 1,
          job_title: "Security Analyst",
          job_responsibilities:
            "Monitor and analyze security events. Implement security tools. Conduct vulnerability assessments.",
          no_of_people_to_train: "=4000*5",
          training_program: "Cybersecurity Skills Accelerator",
          training_modules: [
            "General Security Concepts",
            "Threats, Vulnerabilities, and Mitigations",
            "Security Architecture",
            "Security Operations",
            "Security Program Management and Oversight",
            "Cryptography and PKI",
          ],
          training_duration: "200 hrs",
          training_prerequisite:
            "SHS graduate with Basic IT or Minimum of First Degree",
          available_international_certifications: [
            "CompTIA Security+",
            "GIAC Security Essentials (GSEC)",
          ],
          difficulty_level: "Advanced",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 2,
          job_title: "SOC Analyst",
          job_responsibilities:
            "Monitor and analyze security alerts. Investigate and escalate incidents.",
          no_of_people_to_train: "=4000*5",
          training_program: "",
          training_modules: [],
          training_duration: "",
          training_prerequisite: "",
          available_international_certifications: [],
          difficulty_level: "Intermediate",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 3,
          job_title: "IT Support (Security Focus)",
          job_responsibilities:
            "Provide technical support with a focus on security. Assist in patch management.",
          no_of_people_to_train: "30000",
          training_program: "Certified Cybersecurity Support Technician (CCST)",
          training_modules: [
            "Introduction to Cybersecurity",
            "Networking Basics",
            "Networking Devices and Configuration",
            "Endpoint Security",
            "Network Defence",
            "Cyber Threat Management",
          ],
          training_duration: "140 hrs",
          training_prerequisite: "Basic computer literacy",
          available_international_certifications: [
            "Cisco Certified Support Technician (CCST) Cybersecurity",
          ],
          difficulty_level: "Beginner",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 4,
          job_title: "IT Support (Networking Focus)",
          job_responsibilities:
            "Provide technical support with a focus on networking. Assist in network device management.",
          no_of_people_to_train: "29000",
          training_program: "Certified Network Support Technician (CNST)",
          training_modules: [
            "Networking Basics",
            "Networking Devices and Initial Configuration",
            "Network Addressing and Basic Troubleshooting",
            "Network Support and Security",
          ],
          training_duration: "140 hrs",
          training_prerequisite: "Basic computer literacy",
          available_international_certifications: [
            "Cisco Certified Support Technician (CCST) Networking",
          ],
          difficulty_level: "Beginner",
          image: "/images/courses/network.jpg",
        },
        {
          no: 5,
          job_title: "Cybersecurity Officer",
          job_responsibilities:
            "Learn how to monitor, detect and respond to cyber threats for enterprise systems.",
          no_of_people_to_train: "10000",
          training_program: "Certified Cybersecurity Professional",
          training_modules: [
            "Security Concepts – CIA triad, threats, vulnerabilities",
            "Network Security – TCP/IP risks, IDS/IPS, SIEM basics SOC",
            "Operations – Roles, incident handling, log analysis Threat",
            "Detection – IoCs, threat intelligence, response Endpoint",
            "Security – EDR, malware analysis, forensics",
            "Cryptography – Encryption, PKI, VPNs",
            "Compliance – GDPR, PCI-DSS, security policies",
            "Hands-On Labs – SIEM tools, SOC simulations",
          ],
          training_duration: "200 hrs",
          training_prerequisite:
            "SHS graduate with Basic IT or Minimum of First Degree",
          available_international_certifications: ["CISCO CyberOps Associate"],
          difficulty_level: "Intermediate",
          image: "/images/courses/cybersecuirty-officer.jpg",
        },
      ],
    },
    {
      category: "DATA Protection",
      jobs: [
        {
          no: 6,
          job_title: "Data Protection Officer",
          job_responsibilities:
            "Monitor compliance with data protection laws and policies, detect and assess data protection risks and breaches, respond to data incidents and ensure regulatory compliance.",
          no_of_people_to_train: "10000",
          training_program: "Certified Data Protection Officer – CDPO",
          training_modules: [
            "Introduction to Data Protection & Act 843",
            "Data Protection Principles & Lawful Processing",
            "Rights of Data Subjects & Compliance Obligations",
            "Role of a Data Protection Officer (DPO).",
            "Incident Response & Breach Management",
          ],
          training_duration: "40 hrs",
          training_prerequisite:
            "A minimum of a diploma or bachelor's degree in law, IT, data management, cybersecurity, business administration, or related fields.",
          available_international_certifications: [
            "Certified Information Privacy Professional (CIPP).",
            "Certified Information Privacy Manager (CIPM)",
          ],
          difficulty_level: "Intermediate",
          image: "/images/courses/dpo.JPG",
        },
        {
          no: 7,
          job_title: "Data Protection Manager",
          job_responsibilities:
            "Oversee and coordinate data protection activities within an organization, Ensure data protection policies are implemented and followed, Conduct protection impact assessments (DPIAs), Provide strategic guidance on data protection and privacy risks, Serve as a liaison between the organization and regulatory authorities",
          no_of_people_to_train: "10000",
          training_program: "Certified Data Protection Supervisor – CDPS",
          training_modules: [
            "Privacy by Design & Data Protection Impact Assessments (DPIA)",
            "Managing Data Breaches & Incident Response Plans",
            "Data Protection Audits & Compliance Reporting",
            "Role of the CDPS in Organizational Compliance",
          ],
          training_duration: "60 hrs",
          training_prerequisite:
            "1. Minimum of a bachelor's degree in law, IT, data management, cybersecurity, business administration, or related fields. 2. Must have Completed the CDPO course",
          available_international_certifications: [],
          difficulty_level: "Advanced",
          image: "/images/courses/data-protection-manager.jpg",
        },
        {
          no: 8,
          job_title: "Data Protection Professional",
          job_responsibilities:
            "Compliance Auditing & Enforcement, Privacy by Design Implementation, Cross-Border Data Transfers, Stakeholder Engagement & Training, Incident Response & Policy Development",
          no_of_people_to_train: "10000",
          training_program: "Certified Data Protection Practitioner – CDPP",
          training_modules: [
            "Advanced Compliance Auditing & Enforcement",
            "Operationalizing Privacy by Design",
            "Cross-Border Data Transfers & Legal Mechanisms GDPR.",
            "Stakeholder Engagement & Training technical teams.",
          ],
          training_duration: "40 hrs",
          training_prerequisite:
            "1. Minimum of a Masters degree in law, IT, data management, cybersecurity, business administration, or related fields. 2. Must have Completed the both the CDPO and CDPS course",
          available_international_certifications: [
            "Certified Information Security Manager (CISM).",
            "ISO 27701 Lead Implementer/Lead Auditor",
          ],
          difficulty_level: "Advanced",
          image: "/images/courses/data-protection-practioner.jpg",
        },
        {
          no: 9,
          job_title: "Data Protection Expert",
          job_responsibilities:
            "Advanced Data Protection Strategy & Policy Development, International Data Transfers & Cross-Border Compliance, Privacy Program Management & Regulatory Engagement, Managing Large-Scale Data Protection Programs, Incident Response & Data Breach Management.",
          no_of_people_to_train: "10000",
          training_program: "Certified Data Protection Expert – CDPE",
          training_modules: [
            "Advanced Data Protection Strategy & Policy Development",
            "International Data Transfers & Cross-Border Compliance",
            "Privacy Program Management & Regulatory Engagement",
            "Managing Large-Scale Data Protection Programs",
          ],
          training_duration: "40 hrs",
          training_prerequisite:
            "1. Minimum of a Masters degree in law, IT, data management, cybersecurity, business administration, or related fields. 2. Must have Completed the both the CDPO and CDPS course",
          available_international_certifications: [],
          difficulty_level: "Expert",
          image: "/images/courses/certified-data-protection-expert.jpg",
        },
        {
          no: 10,
          job_title: "DPO, Compliance Officer",
          job_responsibilities:
            "Monitor compliance with data protection laws, advise on privacy policies, and handle data breaches.",
          no_of_people_to_train: "3000",
          training_program:
            "Certified Data Protection and Privacy Practitioner",
          training_modules: [
            "Introduction to GDPR and Ghana's Data Protection Act",
            "GDPR Principles and Legal Framework in Ghana",
            "Rights of Data Subjects in Ghana",
            "Data Protection Impact Assessments (DPIAs) in Ghana",
            "Data Breach Management in Ghana",
            "Role of the Data Protection Officer (DPO) in Ghana",
            "International Data Transfers and Ghana",
            "Accountability and Governance in Ghana",
            "GDPR, Technology, and Ghana's Digital Landscape",
            "Enforcement and Penalties in Ghana",
            "Practical Implementation of GDPR in Ghana",
          ],
          training_duration: "70 hrs",
          training_prerequisite:
            "SHS graduate with Basic IT or Minimum of First Degree",
          available_international_certifications: [
            "Certified Information Privacy Professional (CIPP) OR",
            "Certified GDPR Practitioner",
          ],
          difficulty_level: "Intermediate",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 11,
          job_title: "DPO, Privacy Consultant.",
          job_responsibilities:
            "Implement DPC regulation/GDPR compliance programs, conduct data protection impact assessments, and train staff on DPC regulation /GDPR.",
          no_of_people_to_train: "3000",
          training_program: "",
          training_modules: [],
          training_duration: "",
          training_prerequisite: "",
          available_international_certifications: [],
          difficulty_level: "Advanced",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
      ],
    },
    {
      category: "Artificial Intelligence Training",
      jobs: [
        {
          no: 12,
          job_title: "Data Analyst",
          job_responsibilities:
            "Analyze and preprocess data for models. Create visualizations.",
          no_of_people_to_train: "20000",
          training_program: "Data Analytics Professional",
          training_modules: [
            "Foundations of Data Concepts and Environments",
            "Data Mining Techniques and Methodologies",
            "Advanced Data Analysis and Interpretation",
            "Data Visualization and Storytelling for Decision-Making",
            "Data Governance, Quality Assurance, and Control Frameworks",
          ],
          training_duration: "140 hrs",
          training_prerequisite: "SHS graduate with Basic IT knowledge",
          available_international_certifications: ["CompTIA Data+"],
          difficulty_level: "Beginner",
          image: "/images/courses/data-analyst.jpg",
        },
        {
          no: 13,
          job_title: "Data Analyst (Microsoft Option)",
          job_responsibilities:
            "Analyze and preprocess data for models. Create visualizations using Microsoft Technologies",
          no_of_people_to_train: "20000",
          training_program: "Data Analyst Associate",
          training_modules: [
            "Power BI Basics - Interface, data import, simple visuals",
            "Data Cleaning (Power Query) - Transformations, merging, error handling",
            "Data Modeling & DAX - Star schema, relationships, basic DAX",
            "Advanced Visuals - Custom visuals, interactivity, drill-through",
            "Power BI Service - Publishing, sharing, RLS, refreshes",
            "Optimization - Query folding, aggregations, performance",
          ],
          training_duration: "180 hrs",
          training_prerequisite: "SHS graduate with Basic IT knowledge",
          available_international_certifications: [
            "Microsoft PL-300: Power BI Data Analyst Associate",
          ],
          difficulty_level: "Beginner",
          image: "/images/courses/data-analyst-associate.JPG",
        },
        {
          no: 14,
          job_title: "AI Software Developer",
          job_responsibilities:
            "Develop software with basic AI capabilities. Integrate pre-built AI models.",
          no_of_people_to_train: "20000",
          training_program: "Certified AI Software Developer",
          training_modules: [
            "Introduction to AI and Azure AI Services",
            "Machine Learning Fundamentals",
            "Computer Vision on Azure",
            "Natural Language Processing (NLP) on Azure",
            "Conversational AI and Chatbots",
            "Responsible AI Principles",
            "Azure AI Services Integration",
            "Hands-On Labs and Case Studies",
            "Exam Preparation and Practice",
          ],
          training_duration: "120 hrs",
          training_prerequisite:
            "Basic programming knowledge (Python, Java, etc..)",
          available_international_certifications: [
            "Microsoft Certified: Azure AI Fundamentals",
            "AWS Certified Developer – Associate",
          ],
          difficulty_level: "Intermediate",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 15,
          job_title: "Junior ML Engineer",
          job_responsibilities:
            "Assist in building and deploying ML models. Perform data preprocessing.",
          no_of_people_to_train: "=5000*5",
          training_program: "Associate ML Engineer Program",
          training_modules: [
            "Foundations of MLOps: Principles and Practices",
            "End-to-End Machine Learning Workflow Development",
            "Introduction to Shell Scripting for Automation",
            "MLOps Deployment Strategies and Model Lifecycle Management",
            "Introduction to MLFlow for Experiment Tracking and Model Management",
            "ETL and ELT Pipelines in Python for Data Processing",
            "Data Versioning and Management with DVC (Data Version Control)",
            "Fundamentals of Monitoring Machine Learning Models",
            "Implementing Machine Learning Monitoring in Python",
            "Introduction to Docker for Containerization in ML",
            "Continuous Integration and Continuous Deployment (CI/CD) for Machine Learning Systems",
          ],
          training_duration: "200 hrs",
          training_prerequisite:
            "Basic understanding of ML concepts and Python.",
          available_international_certifications: [
            "TensorFlow Developer Certificate (basic)",
          ],
          difficulty_level: "Advanced",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 16,
          job_title: "Data Engineer",
          job_responsibilities:
            "Design data processing systems, store the data, maintain and automate data workloads, ingest and process the data, and prepare and use data for analysis",
          no_of_people_to_train: "=5000*5",
          training_program: "Certified Data Engineer Program",
          training_modules: [
            "Designing Scalable Data Processing Systems",
            "Data Ingestion and Processing Techniques",
            "Data Storage Solutions and Architecture",
            "Data Preparation and Transformation for Analytical Use Cases",
            "Automation and Maintenance of Data Workloads",
          ],
          training_duration: "140 hrs",
          training_prerequisite:
            "SHS graduate with Basic Programming knowledge or Minimum of First Degree",
          available_international_certifications: [
            "Google Cloud Professional Data Engineer Certification",
          ],
          difficulty_level: "Advanced",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
      ],
    },
    {
      category: "Mobile Application Development",
      jobs: [
        {
          no: 17,
          job_title: "Mobile Developer with Flutter",
          job_responsibilities:
            "Design and develop high-performance mobile applications using Flutter. Translate UI/UX designs and wireframes into clean, maintainable, and efficient code. Optimize application performance and ensure seamless compatibility across multiple devices and platforms.",
          no_of_people_to_train: "25000",
          training_program: "Flutter Mobile App Developer program",
          training_modules: [
            "Master Dart essentials and Flutter-specific concepts.",
            "Design and build complete, polished Flutter applications.",
            "Create rich, interactive, and animated Flutter widgets.",
            "Implement efficient navigation and app interface techniques.",
            "Integrate Google Maps and Firebase for authentication and database.",
            "Customize app icons and add diverse Flutter widgets.",
            "Test, debug, and optimize Flutter code.",
            "Prepare and publish apps on Google Play and Apple App Store.",
          ],
          training_duration: "240 hrs",
          training_prerequisite:
            "SHS graduate with Basic Programming knowledge or Minimum of First Degree",
          available_international_certifications: [
            "Flutter Application Development(AFD-200)",
          ],
          difficulty_level: "Intermediate",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 18,
          job_title: "Mobile Developer with React Native",
          job_responsibilities:
            "Design and implement seamless, pixel-perfect user interfaces (UIs) that ensure a consistent and engaging experience across both iOS and Android platforms. Maintain clean, scalable, and well-documented code while developing robust testing protocols to deliver high-quality, reliable products. Troubleshoot and resolve bugs, and optimize application performance to achieve a native-like user experience.",
          no_of_people_to_train: "25000",
          training_program: "Mobile App Development with React Native",
          training_modules: [
            "Introduction to React Native and Environment Setup",
            "Installation and Configuration of Development Tools",
            "Core Concepts and Fundamentals of React Native",
            "JavaScript Essentials for React Native Development",
            "Deep Dive into React Native Components and Architecture",
            "Styling and Layout Design in React Native",
            "Navigation and Routing in Mobile Applications",
            "Forms, User Input Handling, and Mini Project Development",
            "Code Optimization and Performance Best Practices",
          ],
          training_duration: "240 hrs",
          training_prerequisite:
            "SHS graduate with Basic Programming knowledge JavaScript or Minimum of First Degree",
          available_international_certifications: [
            "Flutter Certified Application Developer",
          ],
          difficulty_level: "Intermediate",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
      ],
    },
    {
      category: "Systems Administration",
      jobs: [
        {
          no: 19,
          job_title: "Junior Systems Administrator",
          job_responsibilities:
            "Understand the architecture of a Linux system; Install, configure, and maintain Linux workstations, including X11 setup and network client configuration;Work efficiently at the Linux command line; utilizing common GNU and Unix commands;Manage files, directories, and access permissions while ensuring system security;Perform routine system maintenance tasks, including user assistance, adding users to larger systems, and managing backups and restores;Execute system shutdowns and reboots as needed.",
          no_of_people_to_train: "10000",
          training_program: "Linux System Administration Foundational Level",
          training_modules: [
            "System Architecture",
            "Linux Installation and Package Management",
            "GNU and Unix Commands",
            "Devices, Linux Filesystems, Filesystem Hierarchy Standard",
            "Shells and Shell Scripting",
            "Interfaces and Desktops",
            "Administrative Tasks",
            "Essential System Services",
            "Networking Fundamentals",
            "Security",
          ],
          training_duration: "160 hrs",
          training_prerequisite: "Basic Digital Skills",
          available_international_certifications: [
            "Linux Professional Institute LPIC-1",
          ],
          difficulty_level: "Beginner",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 20,
          job_title: "Senior Systems Administrator",
          job_responsibilities:
            "Perform advanced Linux system administration, including kernel management, system startup, and maintenance. Manage block storage, file systems, networking, authentication, and system security (firewall, VPN, etc.). Install, configure, and maintain essential network services (DHCP, DNS, SSH, web servers, FTP, NFS, Samba, and email delivery). Supervise junior administrators and provide guidance on automation strategies and hardware/software procurement.",
          no_of_people_to_train: "10000",
          training_program: "Linux System Administration Advanced Level",
          training_modules: [
            "Capacity Planning",
            "Linux Kernel",
            "System Startup",
            "Filesystem and Devices",
            "Advanced Storage Device Administration",
            "Networking Configuration",
            "System Maintenance",
            "Domain Name Server",
            "Web Services",
            "File Sharing",
            "Network Client Management",
            "E-Mail Services",
            "System Security",
          ],
          training_duration: "160 hrs",
          training_prerequisite:
            "Linux System Administration Foundational Level",
          available_international_certifications: [
            "Linux Professional Institute LPIC-2",
          ],
          difficulty_level: "Advanced",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
      ],
    },
    {
      category: "Web Application Programming",
      jobs: [
        {
          no: 21,
          job_title: "Full-Stack PHP Developer",
          job_responsibilities:
            "Build robust MVC based solutions using the Laravel frame work. Develop standalone APIs backends",
          no_of_people_to_train: "=6000*5",
          training_program: "Certified PHP Full-Stack Developer Program",
          training_modules: [
            "PHP basics (Functions, Data types, etc)",
            "Object Oriented Programming(OOP)",
            "Architecture (MVC)",
            "Database, Eloquent ORM",
            "Artisan console, Composer, logging",
            "Routing, Sessions, Caching",
          ],
          training_duration: "400 hrs",
          training_prerequisite:
            "SHS graduate with ICT Elective or Minimum of First Degree",
          available_international_certifications: [
            "Laravel Certification Program",
          ],
          difficulty_level: "Advanced",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 22,
          job_title: "Full-Stack Python Developer",
          job_responsibilities:
            "Full-stack Python developers build complete web applications, handling both front-end and back-end development, including databases, APIs, and ensuring functionality, security, and scalability throughout the software development lifecycle",
          no_of_people_to_train: "=6000*5",
          training_program: "Certified Python Full-Stack Developer Program",
          training_modules: [
            "Design thinking and Problem Solving",
            "Git and GitHub",
            "HTML and CSS",
            "Core Javascript",
            "Object-Oriented Programming",
            "Core Backend Language(Python)",
            "Databases - SQL/NOSQL",
            "ORMs and APIs",
            "Backend Framework(Django/FastAPI)",
            "Security and Scalability",
            "Frontend/JS Framework(React/Angular)",
            "Connecting Frontend and Backend",
            "DevSecOps (Deep Dive)",
            "Artificial Intelligence (Introduction)",
            "Guest Lectures from Industry players and QA",
            "Career pathways, porftfolio building and interview preps",
            "In-Training and Post-Training Mentorship",
            "Career Pathways and Interview Preparation",
          ],
          training_duration: "400 hrs",
          training_prerequisite:
            "SHS graduate with ICT Elective or Minimum of First Degree",
          available_international_certifications: [
            "PCPP – Certified Professional in Python Programming",
          ],
          difficulty_level: "Advanced",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 23,
          job_title: "Full-Stack JavaScript Developer",
          job_responsibilities:
            "Full-stack JavaScript developers build complete web applications, handling both front-end and back-end development, including databases, APIs, and ensuring functionality, security, and scalability throughout the software development lifecycle",
          no_of_people_to_train: "=6000*5",
          training_program: "Certified JavaScript Full-Stack Developer Program",
          training_modules: [
            "Introduction to Web Technologies: HTML, CSS, and JavaScript Fundamentals",
            "Advanced JavaScript Concepts and Modern ES6+ Features",
            "Version Control with Git and Best Practices",
            "Frontend Frameworks: React.js, Angular, or Vue.js",
            "Node.js and TypeScript for Server-Side Development",
            "Backend Frameworks: Express.js, Nest.js, or Adonis.js",
            "Database Management: SQL and NoSQL Databases",
            "Object-Relational Mapping (ORM), Migrations, and Data Models",
            "API Development and Controller Design",
            "Web Application Security Principles and Practices",
            "Testing Methodologies: Unit, Integration, and End-to-End Testing",
            "DevOps: Continuous Integration and Continuous Deployment (CI/CD) Pipelines",
            "Application Deployment Strategies and Tools",
          ],
          training_duration: "400 hrs",
          training_prerequisite:
            "SHS graduate with ICT Elective or Minimum of First Degree",
          available_international_certifications: [
            "IBM Full-Stack JavaScript Developer Professional Certificate",
          ],
          difficulty_level: "Advanced",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
      ],
    },
    {
      category: "BPO Training",
      jobs: [
        {
          no: 24,
          job_title:
            "Customer Support Roles: 1. Customer Service Representative 2. Chat Support Agent 3. Email Support Agent 4. Technical Support Specialist 5. IT Helpdesk Support 6. Social Media Moderator 7. Customer Success Manager",
          job_responsibilities:
            "Agents address customer inquiries via phone, email, or chat, resolve issues, and provide product/service information. They maintain CRM records, escalate complex cases, meet performance metrics, and identify upselling opportunities while following company guidelines, building customer loyalty, and staying updated on products and trends.",
          no_of_people_to_train: "=3000*5",
          training_program: "BPO Customer Service Training Program",
          training_modules: [
            "Communication skills,",
            "Conflict resolution,",
            "Product knowledge.",
            "Customer Relationship Management software",
          ],
          training_duration: "100 hrs",
          training_prerequisite: "SHS graduate with Basic IT knowledge",
          available_international_certifications: [
            "ITIL 4 Foundation Certification",
          ],
          difficulty_level: "Beginner",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 25,
          job_title:
            "Back-Office and Administrative Roles 1. Data Entry Operator 2. Back-Office Executive 3. Virtual Assistant 4. Order Processing Specialist 5. Claims Processor (Insurance/Healthcare) 6. Billing and Collections Specialist",
          job_responsibilities:
            "IT Support Help Desk Network Administration Software Developing",
          no_of_people_to_train: "=3000*5",
          training_program: "BPO Back-office and Administrative Program",
          training_modules: [
            "Troubleshooting,",
            "Networking,",
            "Hardware/software support",
          ],
          training_duration: "100 hrs",
          training_prerequisite: "SHS graduate with Basic IT knowledge",
          available_international_certifications: [
            "CompTIA A+",
            "ITIL",
            "Microsoft Certified: Azure Fundamentals",
          ],
          difficulty_level: "Beginner",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 26,
          job_title:
            "IT and Technical Roles 1. Technical Support Specialist 2. IT Helpdesk Support 3. Software Support Engineer 4. Cybersecurity Analyst",
          job_responsibilities:
            "Data Entry Operator Data Analyst Database Administrator Data Scientist",
          no_of_people_to_train: "=3000*5",
          training_program: "BPO Technical and IT Support Training Program",
          training_modules: [
            "Data entry",
            "Database management",
            "Data visualization tools (e.g., excel, sql)",
          ],
          training_duration: "100 hrs",
          training_prerequisite: "SHS graduate with Basic IT knowledge",
          available_international_certifications: [
            "ITIL 4 Foundation Certification",
          ],
          difficulty_level: "Intermediate",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 27,
          job_title: "BPO Manager and Operators",
          job_responsibilities:
            "BPO Operations Manager Team Supervision Quality Assurance Analysis Training and Development",
          no_of_people_to_train: "=1000*5",
          training_program: "BPO Leadership and Operations Management Program",
          training_modules: [
            "Fundamentals of Customer Management",
            "Contact Center Operations",
            "Customer Experience (CX) Strategy",
            "Leadership in Customer Management",
            "Advanced Communication Skills",
            "Data-Driven Decision Making",
            "Technology in Customer Management",
            "Customer Retention and Loyalty",
            "Compliance and Ethics in Customer Management",
            "Crisis Management in Customer Service",
          ],
          training_duration: "140 hrs",
          training_prerequisite: "Minimum of First Degree",
          available_international_certifications: [
            "Certified BPO Operations Manager (CBOM™)",
          ],
          difficulty_level: "Advanced",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
      ],
    },
    {
      category: "Other Special Training Programs",
      jobs: [
        {
          no: 28,
          job_title: "Gaming",
          job_responsibilities: "Game Programmer",
          no_of_people_to_train: "10000",
          training_program: "The Game Programmer Program",
          training_modules: [
            "Unity Basics – Editor navigation, GameObjects, asset import",
            "C# Fundamentals – Variables, loops, methods, debugging",
            "Gameplay Coding – Movement, collisions, spawning objects",
            "UI Systems – Canvases, buttons, scene management",
            "Physics – Rigidbodies, forces, raycasting",
            "Data & Optimization – ScriptableObjects, Profiler, pooling",
          ],
          training_duration: "150 hrs",
          training_prerequisite: "SHS graduate with Basic IT knowledge",
          available_international_certifications: [
            "Unity Certified User (UCU) – Programmer",
          ],
          difficulty_level: "Intermediate",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 29,
          job_title: "Animation",
          job_responsibilities: "Animation Programmer",
          no_of_people_to_train: "10000",
          training_program: "The Animation Programmer Course",
          training_modules: [
            "Maya Basics – Interface, navigation, projects",
            "3D Modeling – Polygons, NURBS, UVs",
            "Animation – Keyframes, Graph Editor, simple rigging",
            "Materials/Lighting – Hypershade, Arnold, 3-point lighting",
            "Rendering – Arnold settings, output formats",
          ],
          training_duration: "150 hrs",
          training_prerequisite: "SHS graduate with Basic IT knowledge",
          available_international_certifications: [
            "Autodesk Maya Certified User",
          ],
          difficulty_level: "Intermediate",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
        {
          no: 30,
          job_title: "STEM",
          job_responsibilities:
            "STEM Instructor/ Programmer, Robotics Programmer, Digital Marketer",
          no_of_people_to_train: "5000",
          training_program: "STEM & Digital Skills Associate (SDSA)",
          training_modules: [
            "Design Thinking & Problem Solving.",
            "STEM Instructor Training: Coding & Robotics Using STEMAIDE Kits.",
            "Artificial Intelligence (AI) & Prompt Engineering.",
            "Digital & Social Media Marketing.",
            "Capstone Project & Career Development.",
          ],
          training_duration: "100 hrs",
          training_prerequisite:
            "SHS graduate with ICT Elective or Minimum of First Degree",
          available_international_certifications: [
            "Designed by the IoT Network Hub, Ghana",
          ],
          difficulty_level: "Beginner",
          image: "/images/hero/Certified-Data-Protection-Manager.jpg",
        },
      ],
    },
  ],
  total_trainees:
    "=SUM(E5:E9, E11:E16,E18:E22,E24:E25,E27:E28,E30:E32,E34:E37,E39:E41)",
};

export { courses };
