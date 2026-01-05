<?php
require_once __DIR__ . '/config/db.php';

try {
    // 1. Create Departments
    $departments = ['Engineering', 'Product', 'Human Resources', 'Marketing', 'Design'];
    foreach ($departments as $dept) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO departments (name) VALUES (?)");
        $stmt->execute([$dept]);
    }

    // Get IDs
    $deptIds = [];
    $stmt = $pdo->query("SELECT id, name FROM departments");
    while ($row = $stmt->fetch()) {
        $deptIds[$row['name']] = $row['id'];
    }

    // 2. Sample Job Templates
    $jobTemplates = [
        [
            'title' => 'Senior Product Designer',
            'department' => 'Design',
            'description' => 'Leading design initiatives across our mobile and desktop applications.',
            'requirements' => '- 5+ years of experience\n- Proficiency in Figma',
            'employment_type' => 'Full-time',
            'location' => 'New York, NY (Remote)',
            'salary_range' => '$140k - $180k',
            'status' => 'published',
            'experience_level' => 'Senior Level',
            'min_salary' => 140000.00,
            'max_salary' => 180000.00
        ],
        [
            'title' => 'Backend Engineer',
            'department' => 'Engineering',
            'description' => 'Building high-scale distributed systems.',
            'requirements' => '- Strong Go or Java experience',
            'employment_type' => 'Full-time',
            'location' => 'San Francisco, CA',
            'salary_range' => '$160k - $220k',
            'status' => 'published',
            'experience_level' => 'Senior Level',
            'min_salary' => 160000.00,
            'max_salary' => 220000.00
        ],
        [
            'title' => 'HR Manager',
            'department' => 'Human Resources',
            'description' => 'Manage employee relations and talent acquisition.',
            'requirements' => '- 3+ years in HR',
            'employment_type' => 'Contract',
            'location' => 'London, UK',
            'salary_range' => '£60k - £80k',
            'status' => 'published',
             'experience_level' => 'Mid Level',
            'min_salary' => 60000.00,
            'max_salary' => 80000.00
        ],
         [
            'title' => 'Frontend Engineer',
            'department' => 'Engineering',
            'description' => 'Crafting modern software development tools.',
            'requirements' => '- React and TypeScript expert',
            'employment_type' => 'Full-time',
            'location' => 'Remote',
            'salary_range' => '$130k - $170k',
            'status' => 'published',
            'experience_level' => 'Mid Level',
            'min_salary' => 130000.00,
            'max_salary' => 170000.00
        ],
        [
            'title' => 'Junior Copywriter',
            'department' => 'Marketing',
            'description' => 'Copywriter needed for initial landing pages.',
            'requirements' => '- Good english skills',
            'employment_type' => 'Part-time',
            'location' => 'Remote',
            'salary_range' => '$40k - $60k',
            'status' => 'published',
            'experience_level' => 'Entry Level',
            'min_salary' => 40000.00,
            'max_salary' => 60000.00
        ]
    ];

    // Clear existing for clean seed
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE job_postings");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Generate 25 jobs
    for ($i = 0; $i < 5; $i++) {
        foreach ($jobTemplates as $template) {
            $deptId = $deptIds[$template['department']] ?? null;
            // Add slight variation to title to distinguish
            $title = $template['title'] . ($i > 0 ? " " . ($i+1) : "");
            
            $stmt = $pdo->prepare("INSERT INTO job_postings (title, department_id, description, requirements, employment_type, location, salary_range, status, experience_level, min_salary, max_salary, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // Randomize creation time for sorting
            $createdAt = date('Y-m-d H:i:s', strtotime("-" . rand(1, 20) . " days"));

            $stmt->execute([
                $title,
                $deptId,
                $template['description'],
                $template['requirements'],
                $template['employment_type'],
                $template['location'],
                $template['salary_range'],
                $template['status'],
                $template['experience_level'],
                $template['min_salary'],
                $template['max_salary'],
                $createdAt
            ]);
        }
    }

    echo "Database seeded successfully with 25 jobs for pagination testing!";

} catch (PDOException $e) {
    echo "Seeding failed: " . $e->getMessage();
}
