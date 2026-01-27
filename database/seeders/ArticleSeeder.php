<?php

namespace Database\Seeders;

use App\Models\Article;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $articles = [
            [
                'user_id' => 3,
                'title' => 'Understanding Philippine Customs Clearance Process',
                'content' => 'Navigating the Philippine customs clearance process can be complex, but with the right guidance, businesses can ensure smooth import and export operations. Learn about the essential steps, required documentation, and compliance requirements that help you move goods faster and more efficiently across borders. From initial submission to final release, understanding each phase is crucial for avoiding delays and penalties.',
                'image_url' => 'articles/images/sample1.jpg',
            ],
            [
                'user_id' => 3,
                'title' => 'PEZA Compliance: A Complete Guide for Businesses',
                'content' => 'Philippine Economic Zone Authority (PEZA) compliance is essential for businesses operating in economic zones. This comprehensive guide covers PEZA registration requirements, tax incentives, regulatory obligations, and best practices for maintaining compliance. Learn how proper PEZA processing can optimize your operations and ensure you maximize the benefits of operating within these special economic zones.',
                'image_url' => 'articles/images/sample2.jpg',
            ],
            [
                'user_id' => 3,
                'title' => 'Import Documentation Essentials: What You Need to Know',
                'content' => 'Accurate documentation is the foundation of successful customs clearance. This article explores the essential documents required for importing goods into the Philippines, including commercial invoices, packing lists, bills of lading, and special permits. Understanding these requirements helps prevent costly delays and ensures strict compliance with Bureau of Customs regulations.',
                'image_url' => 'articles/images/sample3.jpg',
            ],
            [
                'user_id' => 3,
                'title' => 'FDA and BFAR Permits: Navigating Regulatory Requirements',
                'content' => 'Importing food, drugs, cosmetics, or agricultural products requires specialized permits from FDA (Food and Drug Administration) and BFAR (Bureau of Fisheries and Aquatic Resources). This guide breaks down the application process, required documents, processing times, and compliance standards to help you successfully obtain and maintain these critical permits for your business operations.',
                'image_url' => 'articles/images/sample4.jpg',
            ],
            [
                'user_id' => 3,
                'title' => 'Freight Forwarding Solutions: Domestic and International',
                'content' => 'Effective freight forwarding is essential for businesses engaged in trade. Discover how integrated freight solutions covering sea, air, and land transportation can streamline your supply chain. Learn about route optimization, cargo insurance, tracking capabilities, and how partnering with an experienced freight forwarder ensures your shipments reach their destination safely and on time across 1,247 seaports and airports worldwide.',
                'image_url' => 'articles/images/sample5.jpg',
            ],
            [
                'user_id' => 3,
                'title' => 'Post-Clearance Audit: What Importers Should Expect',
                'content' => 'Post-clearance audits are a crucial part of customs compliance. Understanding what triggers an audit, how to prepare, and what documentation to maintain can save businesses from penalties and disputes. This article provides insights into the audit process, common findings, and best practices for maintaining compliance with Philippine customs regulations even after your goods have been cleared.',
                'image_url' => 'articles/images/sample6.jpg',
            ],
            [
                'user_id' => 3,
                'title' => 'Customs Dispute Resolution: Protecting Your Business Interests',
                'content' => 'When customs disputes arise, knowing how to effectively resolve them is critical. Learn about the various dispute resolution mechanisms available, including administrative protests, formal appeals, and negotiation strategies. This guide helps businesses understand their rights, prepare strong cases, and work towards favorable outcomes while maintaining positive relationships with customs authorities.',
                'image_url' => 'articles/images/sample7.jpg',
            ],
            [
                'user_id' => 3,
                'title' => 'Project Cargo Handling: Specialized Solutions for Complex Shipments',
                'content' => 'Moving oversized, heavy, or high-value cargo requires specialized expertise and careful planning. Explore the unique challenges of project cargo logistics, including route surveys, specialized equipment, regulatory approvals, and risk management. Learn how experienced customs brokers and freight forwarders coordinate these complex operations to ensure your critical shipments are delivered successfully.',
                'image_url' => 'articles/images/sample8.jpg',
            ],
        ];

        foreach ($articles as $article) {
            Article::create($article);
        }
    }
}
