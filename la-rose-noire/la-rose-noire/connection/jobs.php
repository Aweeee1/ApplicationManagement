<?php
// Open roles shown on the public careers page. Edit this list to add, change or remove a role.
// 'days' = how many days ago it was posted. The first four roles follow your current careers page;
// the Quality Control and Supply Chain roles are placeholders to replace with real ones.
function jobs(): array {
    $loc = 'Clark Freeport Zone, Pampanga';
    return [
        ['id' => 'ac-technician', 'dept' => 'Engineering', 'type' => 'Full-time', 'title' => 'AC Technician', 'loc' => 'Clark', 'shift' => 'Day shift', 'days' => 7,
         'edu' => 'Preferably High School or Vocational School graduate',
         'summary' => 'We are seeking a reliable and safety-oriented ACU Technician to join our Engineering team in Clark. In this role, you will handle the routine maintenance, cleaning, assessment, and electrical troubleshooting of our Air-Conditioning and Air-Handling Units (ACU/AHU).',
         'about' => 'Reporting directly to the Engineering Manager, the ACU Technician ensures all cooling and air-handling systems operate efficiently and safely within our manufacturing facility. You will execute preventive maintenance SOPs, perform proper nitrogen handling and purging, conduct basic electrical troubleshooting, and immediately escalate any food safety or product integrity concerns.',
         'resp' => ['Perform preventive and corrective maintenance on Air-Conditioning Units (ACU) and Air-Handling Units (AHU).', 'Conduct thorough unit cleaning and regular equipment assessments.', 'Perform equipment and basic electrical troubleshooting.', 'Report any food and workplace safety issues promptly to higher management.', 'Perform other related duties as may be assigned from time to time.'],
         'qual' => ['Preferably a High School or Vocational School graduate.', 'At least 1 year of hands-on experience in a manufacturing industry.', 'Preferably holds a relevant TESDA certification.', 'Completed Food Safety Training (or willing to undergo training).', 'Familiar with ACU/AHU maintenance SOPs and proper nitrogen handling/purging.', 'Adaptable, safety-oriented, accountable, and able to handle complex tasks positively.']],
        ['id' => 'ac-tech-supervisor', 'dept' => 'Engineering', 'type' => 'Full-time', 'title' => 'AC-Tech Supervisor', 'loc' => $loc, 'shift' => 'Day shift', 'days' => 7,
         'summary' => 'We are seeking an experienced AC-Tech Supervisor to lead our Engineering air-conditioning team in Clark Freeport Zone. You will oversee HVAC system maintenance, troubleshoot technical issues using schematic diagrams, and supervise shift operations to ensure uninterrupted facility climate control.',
         'about' => 'You will lead a team of technicians and keep every cooling and air-handling system running safely, on schedule and within food-safety requirements.',
         'resp' => ['Plan and supervise preventive and corrective HVAC maintenance.', 'Troubleshoot faults using schematic diagrams.', 'Schedule and coach technicians on each shift.', 'Keep maintenance records and report to the Engineering Manager.'],
         'qual' => ['Technical or engineering background with HVAC experience.', 'At least 3 years in a manufacturing environment, including some supervisory work.', 'Able to read electrical and refrigeration schematics.']],
        ['id' => 'engineering-helper', 'dept' => 'Engineering', 'type' => 'Contract', 'title' => 'Engineering Helper', 'loc' => $loc, 'shift' => 'Day shift', 'days' => 7,
         'summary' => 'We are seeking a reliable Engineering Helper on a 1-month contract for a ceiling replacement project in Clark Freeport Zone, assisting with masonry, material handling, and general construction tasks.',
         'about' => 'A one-month contract role supporting our engineers on a ceiling replacement project inside the facility.',
         'resp' => ['Assist with masonry and general construction tasks.', 'Move and organise materials and tools.', 'Follow site safety and food-safety rules at all times.'],
         'qual' => ['Physically fit and able to follow instructions carefully.', 'Previous construction or maintenance helper experience is an advantage.']],
        ['id' => 'facilities-supervisor', 'dept' => 'Engineering', 'type' => 'Full-time', 'title' => 'Facilities Supervisor – Mechanical Eng’r.', 'loc' => $loc, 'shift' => 'Day shift', 'days' => 7,
         'summary' => 'We are seeking a licensed Facilities Supervisor – Mechanical Engineer to lead daily facility operations and preventive maintenance at our Clark Freeport Zone manufacturing site. You will oversee MEPF, HVAC, refrigeration, and building systems while supervising the team.',
         'about' => 'You will be responsible for the day-to-day condition of the plant: mechanical, electrical, plumbing and fire-protection systems, plus the people who maintain them.',
         'resp' => ['Lead daily facility operations and maintenance schedules.', 'Oversee MEPF, HVAC and refrigeration systems.', 'Supervise technicians and contractors.', 'Support audits and keep safety records up to date.'],
         'qual' => ['Licensed Mechanical Engineer.', 'At least 3 years of facilities or plant maintenance experience.', 'Strong leadership and communication skills.']],
        ['id' => 'qc-inspector', 'dept' => 'Quality Control', 'type' => 'Full-time', 'title' => 'QC Inspector', 'loc' => 'Clark', 'shift' => 'Day shift', 'days' => 14,
         'summary' => 'Placeholder role: inspect incoming materials and finished products against our quality standards, record results and flag anything that does not meet specification.',
         'about' => 'Placeholder text. Describe the role, who it reports to and what a typical day looks like.',
         'resp' => ['Inspect materials and products against specification.', 'Record results accurately.', 'Report non-conformities immediately.'],
         'qual' => ['Attention to detail.', 'Food safety training (or willing to undergo it).']],
        ['id' => 'qa-analyst', 'dept' => 'Quality Control', 'type' => 'Full-time', 'title' => 'Quality Assurance Analyst', 'loc' => 'Clark', 'shift' => 'Day shift', 'days' => 21,
         'summary' => 'Placeholder role: keep our quality documents, audits and corrective actions in order, and help the team meet food-safety standards.',
         'about' => 'Placeholder text. Describe the role, who it reports to and what a typical day looks like.',
         'resp' => ['Maintain quality documents and records.', 'Support internal and external audits.', 'Track corrective actions to completion.'],
         'qual' => ['Degree in food technology, chemistry or a related field.', 'Good written communication.']],
        ['id' => 'supply-chain-planner', 'dept' => 'Supply Chain', 'type' => 'Full-time', 'title' => 'Supply Chain Planner', 'loc' => 'Clark', 'shift' => 'Day shift', 'days' => 30,
         'summary' => 'Placeholder role: plan purchasing, stock levels and deliveries so production never waits for materials.',
         'about' => 'Placeholder text. Describe the role, who it reports to and what a typical day looks like.',
         'resp' => ['Plan purchases and stock levels.', 'Coordinate with suppliers and production.', 'Report on stock and delivery performance.'],
         'qual' => ['Experience in planning, purchasing or inventory.', 'Comfortable with spreadsheets.']],
    ];
}
