<?php
ob_start();
require_once __DIR__ . '/inc/functions.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

$task = $input['task'] ?? '';
$context = $input['context'] ?? '';
$extra = $input['extra'] ?? '';

if (!$task) {
    json_response(['success' => false, 'error' => 'Missing task'], 400);
}

// blog_ideas accepts object context, skip string validation for it
$json_tasks = [];

if ($task === 'blog_ideas') {
    $systemPrompt = "Return ONLY a valid JSON array of strings. No markdown. No explanation. No code fences.";
    $userMessage = "You are an expert SEO strategist, content marketer, and B2B lead generation specialist.\n\nGenerate 5 high-quality blog post titles for a digital agency that offers:\n\nWebsite Development\nSEO & Local SEO\nPersonal Brand Growth Management\nLinkedIn Growth & Ghostwriting\nAI Content Systems\nMarketing Automation\n\nTarget audiences:\n\nDentists\nLawyers\nReal Estate Agents\nConsultants\nCoaches\nLocal Service Businesses\nTravel Agencies\nImmigration Consultants\n\nRequirements:\n\nFocus on topics that are trending in 2026 and likely to generate organic traffic.\nPrioritize educational content that teaches something valuable.\nInclude emerging topics such as AI, personal branding, local SEO, automation, lead generation, LinkedIn growth, and business growth.\nCreate titles that attract potential clients who may later purchase agency services.\nMix content types:\nHow-to guides\nCase studies\nIndustry trends\nMistakes to avoid\nChecklists\nComparisons\nGrowth strategies\nOptimize titles for search intent and click-through rate.\nAvoid generic agency-focused titles. Focus on solving real business problems.\nInclude industry-specific topics whenever relevant.\nMake titles feel modern, practical, and actionable.\nReturn ONLY a valid JSON array of strings.\n\nExample format:\n\n[\n\"How Dentists Are Using AI Content Systems to Generate More Patient Bookings in 2026\",\n\"Local SEO vs Google Ads for Lawyers: Which Delivers Better ROI?\",\n\"The LinkedIn Strategy Helping Consultants Generate 5X More Inbound Leads\"\n]";
} elseif ($task === 'image_prompt' && is_array($context)) {
    $title = strip_tags($context['title'] ?? '');
    $excerpt = strip_tags($context['excerpt'] ?? '');
    $imgStyle = get_setting('image_gen_style', '');
    $styleNote = $imgStyle ? " Use this consistent visual style: {$imgStyle}" : "";
    $systemPrompt = "You are an expert at writing AI image generation prompts. Write a detailed, vivid prompt for a professional blog featured image. Return ONLY the prompt text, no explanation." . $styleNote;
    $userMessage = "Write an AI image generation prompt for a blog post titled: \"{$title}\" " . ($excerpt ? "Content summary: {$excerpt}" : "") . " The image should be professional, modern, suitable for a digital agency blog. Include: lighting, composition, color palette, mood. Return only the prompt text (2-3 sentences max)." . ($imgStyle ? " Apply this visual style: {$imgStyle}" : "");
} elseif ($task === 'portfolio_case_study') {
    $details = is_array($context) ? $context : json_decode($context, true) ?? [];
    $name = strip_tags($details['project_name'] ?? '');
    $client = strip_tags($details['client'] ?? '');
    $service = strip_tags($details['service'] ?? '');
    $desc = strip_tags($details['description'] ?? '');
    $systemPrompt = "You are a senior case study writer for a creative digital agency. Based on the project details provided, generate a complete case study. Return ONLY a valid JSON object with these keys: challenge (2-3 sentences describing the client's problem), solution (3-5 sentences describing what was delivered), results (2-3 sentences with measurable outcomes), testimonial (a 1-sentence client testimonial quote). No markdown fences, no explanation.";
    $userMessage = "Write a case study for:\nProject: {$name}\nClient: {$client}\nService: {$service}\nDescription: {$desc}";
    $json_tasks[] = 'portfolio_case_study';
} else {
    if (!$context) {
        json_response(['success' => false, 'error' => 'Missing context'], 400);
    }
    $context = substr(strip_tags($context), 0, 3000);
    $extra = substr(strip_tags($extra), 0, 1000);

    $systemPrompts = [
        'fix_grammar' => "You are a professional proofreader. Fix grammar, spelling, and punctuation. Return only the corrected text, no explanations, no quotes, no labels.",

        'improve' => "You are a professional editor. Improve the given text — fix grammar, spelling, and awkward phrasing. Make sentences clearer and more natural. Do NOT add bold, italic, markdown, or any special formatting. No bullet points or lists unless the original had them. Preserve the original structure and meaning. Return only plain text. Do not add any extra sentences, explanations, or commentary. Just return the improved title only.",

        'improve_content' => "You are a professional note editor. Improve the given text — fix grammar, spelling, and awkward phrasing. Make the content clearer, more organized, and well-structured. Use minimal formatting to improve readability: add bullet points (•) for lists, numbered lists (1. 2. 3.) for steps, emoji (📝 ✅ 🚀 etc.) sparingly to highlight key points, and short headings (· HEADING · or similar) to separate sections where appropriate. Keep paragraphs short. Do NOT use bold, italic, markdown, HTML tags, or code fences. Return only plain text with appropriate line breaks and formatting characters. Do not add any extra sentences, explanations, or commentary. Just return the improved content only.",

        'shorten' => "You are an editor. Shorten the given text to be concise and punchy while preserving all key meaning. Return only the shortened text, no explanations.",

        'blog_generate' => "You are an expert blog writer. Write a complete blog post based on the given title. Include an introduction, 3-4 body sections with H2 headings, and a conclusion. Write in a professional yet engaging tone. Return only the HTML content (use <h2> for headings, <p> for paragraphs). Do not wrap in markdown or code fences.",

        'blog_outline' => "You are a content strategist. Create a structured blog post outline from the given topic. Return an outline with H2 headings only, one per line. Do not number them. Return only the headings, no explanations.",

        'blog_section' => "You are an expert blog writer. Write one complete blog section for the given heading. Write 2-3 paragraphs of engaging content. Return only the HTML (<h2> heading + <p> paragraphs). Do not wrap in markdown or code fences.",

        'blog_meta' => "You are an SEO specialist. From the given blog content, generate:\n- meta_title: an actual SEO page title (not just keywords), under 60 characters\n- meta_description: a compelling search result summary, under 160 characters\n- tags: 5-8 comma-separated keywords\n\nReturn ONLY a JSON object with keys: meta_title, meta_description, tags. No other text.",

        'blog_tone' => "You are a copywriter. Rewrite the given text in the specified tone: " . $extra . ". Return only the rewritten text, no explanations.",

        'service_description' => "You are a copywriter. Write a compelling 2-3 sentence service description for the given service name. Make it sell the service. Return only the description text, no explanations.",

        'service_features' => "You are a product manager. Generate 5-6 feature bullet points for the given service. Each feature should be a concise benefit. Return as a JSON array of strings. No other text.",

        'service_hashtags' => "You are a social media strategist. Generate 4 relevant hashtags for the given service. Format as #Hashtag one per line. Return only the hashtags.",

        'package_features' => "You are a pricing strategist. Generate 6-8 feature items appropriate for the given package name and tier. Each feature should be a concise bullet point. Return as a JSON array of strings. No other text.",

        'package_tagline' => "You are a copywriter. Write one compelling sentence as a tagline for the given pricing package. This appears under the price on a pricing card. Return only the tagline, no explanations.",

        'testimonial_improve' => "You are an editor. Polish the given client testimonial to sound natural and compelling while keeping the client's original voice and meaning. Return only the improved quote, no explanations.",

        'faq_generate' => "You are a content writer. From the given topic, generate one FAQ question and a detailed, honest answer. Return as a JSON object with keys: question, answer. No other text.",

        'faq_improve' => "You are an editor. Improve the given FAQ answer to be clearer, more helpful, and professionally worded. Return only the improved answer, no explanations.",

        'portfolio_description' => "You are a copywriter. Write a compelling project description for a portfolio based on the project name and client. Keep it 2-4 sentences. Return only the description text, no explanations.",

        'portfolio_case_study' => "You are a senior case study writer for a creative digital agency. Based on the project details provided, generate a complete case study. Return ONLY a valid JSON object with these keys: challenge (2-3 sentences describing the client's problem), solution (3-5 sentences describing what was delivered), results (2-3 sentences with measurable outcomes), testimonial (a 1-sentence client testimonial quote). No markdown fences, no explanation.",

        'image_prompt' => "You are a creative AI artist. Given the following blog title and content, generate a short image generation prompt (1-2 sentences) suitable for Midjourney, DALL-E, or Stable Diffusion. Describe the visual scene, style, mood, and composition. Return only the prompt text, no explanations.",

        'improve_bio' => "You are a professional copywriter. Improve the given founder/owner biography to be more compelling, professional, and engaging. Keep it concise (2-4 sentences). Return only the improved bio text, no explanations.",

        'blog_generate_all' => "You are an expert SEO blog writer for a digital design agency. Write a complete blog post based on the given keyword/topic. Return ONLY a valid JSON object with these exact keys: title (50-60 char SEO title), content (800+ word HTML blog post using h2 h3 p ul tags), image_prompt (detailed AI image generation prompt for the featured image), meta_title (max 60 chars), meta_description (max 160 chars with CTA), tags (comma separated string of 5 tags). No markdown fences, no explanation, no other text.",

        'blog_title_suggestions' => "You are a creative content strategist. Generate 10 blog post title ideas for a digital agency offering Logo Design, Social Media Kit, Website Development, Branding, SEO, and Graphic Design services. Return ONLY a valid JSON array of 10 title strings. No markdown fences, no explanation, no other text.",

        'blog_seo_title' => "You are an SEO title specialist. Generate a compelling blog post title under 60 characters following these rules:\n- Be descriptive and promise clear value\n- Front-load the primary target keyword\n- Match search intent (informational, how-to, listicle, etc.)\n- Be clickable and engaging\n- Under 60 characters\n\nReturn ONLY the title text, no quotes, no labels.\n\nTopic: ",

        'blog_seo_full' => "You are an SEO content expert. Generate a complete, well-researched blog post following this exact anatomy:\n\nTHE HEADLINE (H1): Must be descriptive, promise clear value, include the primary target keyword, under 60 characters.\n\nTHE HOOK (Introduction): 100-150 words. State the problem, establish authority, explain exactly what the reader will learn.\n\nTABLE OF CONTENTS: A clickable list of H2 subheadings allowing readers to skip to sections.\n\nSUBHEADINGS (H2 & H3): Clear conceptual markers that break up text and help search engines understand the document flow.\n\nBODY PARAGRAPHS: 2-3 sentences max per paragraph for mobile readability.\n\nVISUAL ANCHORS: Place [Image: descriptive alt text] markers every 300-400 words.\n\nCONCLUSION & CTA: A concise wrap-up. No new data. Explicitly direct the user to take a next step.\n\nWORD COUNT: Target 1,400-1,500 words for a standard informational post.\n\nSEO RULES:\n- Primary keyword in the first 100 words\n- Naturally weave keywords into H2/H3 subheadings and image alt text\n- Meta title under 60 chars\n- Meta description 150-160 chars with a CTA\n- URL slug with primary keyword\n- 2-3 internal links + 1 external link to a high-authority site\n- No filler words or complex phrasing\n\nReturn the content as a JSON object with keys: title, content (full HTML), meta_title, meta_description, tags (comma-separated), slug. No markdown fences.",
    ];

    // Note AI tasks — handled inline (before systemPrompts since they're not in that map)
$noteTasks = ['note_improve','note_summarize','note_expand','note_grammar','note_translate_bangla','note_continue','note_generate','note_action_items','note_tags_suggest','note_title_suggest'];
if (in_array($task, $noteTasks)) {
        $systemPrompt = "You are Raihan Islam's personal assistant at Xoos Digital in Dhaka, Bangladesh. You help manage notes about clients, projects, ideas, and business operations. Be concise, practical, and focused on actionable insights. Always respond in the same language as the note content.";

        $notePrompts = [
            'note_improve' => "Improve the following note text — make it clearer, better structured, more professional. Preserve the original meaning and formatting. Return only the improved text.\n\n",
            'note_summarize' => "Summarize the following note into 3-5 concise bullet points. Return as HTML bullet list using <ul><li> tags.\n\n",
            'note_expand' => "Expand the following text with more detail and context. Make it more comprehensive while preserving the original meaning. Return only the expanded text.\n\n",
            'note_grammar' => "Fix all grammar and spelling errors in the following text. Preserve the original tone, style, and formatting. Return only the corrected text.\n\n",
            'note_translate_bangla' => "Translate the following text to Bangla (Bengali). Preserve the structure and any formatting. Return only the translated text.\n\n",
            'note_continue' => "Given the existing note content below, write the next logical paragraph. Match the tone, style, and subject matter. Return only the continuation text.\n\n",
            'note_generate' => "Write a complete, well-structured note based on this topic. Include a brief outline, main content with sections, and key takeaways. Return as formatted text with markdown.\n\n",
            'note_action_items' => "Extract all action items, tasks, and to-dos from the following note. Return as a JSON array of strings like [\"Task 1\", \"Task 2\"].\n\n",
            'note_tags_suggest' => "Based on the following note content, suggest 3-5 relevant tags. Return as a JSON array of strings like [\"tag1\", \"tag2\"].\n\n",
            'note_title_suggest' => "Based on the following note content, suggest 3 alternative titles. Return as a JSON array of strings.\n\n",
        ];

        $userMessage = ($notePrompts[$task] ?? '') . $context;

        $noteJsonTasks = ['note_action_items', 'note_tags_suggest', 'note_title_suggest'];
        if (in_array($task, $noteJsonTasks)) {
            $json_tasks[] = $task;
        }
    } else {
        // Regular tasks — use systemPrompts map
        if (!isset($systemPrompts[$task])) {
            json_response(['success' => false, 'error' => 'Unknown task: ' . $task], 400);
        }

        $systemPrompt = $systemPrompts[$task];
        $userMessage = $context;

        if ($task === 'blog_tone') {
            $userMessage = "Tone: " . $extra . "\n\nText:\n" . $context;
        }

        // Append master image generation style to image-related prompts
        if (in_array($task, ['image_prompt', 'blog_generate_all'])) {
            $imgStyle = get_setting('image_gen_style', '');
            if ($imgStyle) {
                $systemPrompt .= " For the image_prompt, use this consistent visual style: {$imgStyle}";
            }
        }
    }
}

$messages = [
    ['role' => 'system', 'content' => $systemPrompt],
    ['role' => 'user', 'content' => $userMessage],
];

// Select AI feature based on task type
$blogTasks = ['blog_generate','blog_outline','blog_section','blog_generate_all','blog_ideas',
              'blog_title_suggestions','blog_seo_title','blog_seo_full','blog_meta','blog_tone','image_prompt'];
$feature = in_array($task, $blogTasks) ? 'blog' : 'admin';
$settings = ai_feature_settings($feature);

$maxTokens = in_array($task, ['blog_seo_full', 'blog_generate', 'blog_generate_all']) ? 3000 : 1000;

try {
    $reply = ai_call($settings, $messages, $maxTokens, 0.7);
} catch (Throwable $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
    exit;
}

// Strip markdown code fences for JSON-expected tasks
$json_tasks = array_merge($json_tasks, ['blog_meta', 'service_features', 'package_features', 'faq_generate', 'blog_seo_full', 'blog_generate_all', 'blog_title_suggestions', 'blog_ideas']);
if (in_array($task, $json_tasks)) {
    $reply = preg_replace('/```(?:json)?\s*/i', '', $reply);
    $reply = preg_replace('/```/', '', $reply);
    $reply = trim($reply);
}

// Try to parse JSON if the task expects structured output
if (in_array($task, $json_tasks)) {
    $parsed = json_decode($reply, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        json_response(['success' => true, 'data' => $parsed]);
        exit;
    }
}

json_response(['success' => true, 'data' => $reply]);
