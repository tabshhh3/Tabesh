<?php
/**
 * AI Browser Sidebar Template
 *
 * Modern sidebar interface that slides in from the right (RTL).
 *
 * @package Tabesh
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<!-- AI Browser Floating Button -->
<button class="tabesh-ai-browser-toggle" id="tabesh-ai-browser-toggle" aria-label="<?php echo esc_attr__( 'باز کردن دستیار هوشمند', 'tabesh' ); ?>">
	<svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
		<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
		<circle cx="9" cy="10" r="1" fill="currentColor"></circle>
		<circle cx="15" cy="10" r="1" fill="currentColor"></circle>
		<path d="M9 15h6"></path>
	</svg>
	<span class="tabesh-ai-browser-notification-badge" style="display: none;"></span>
</button>

<!-- Overlay for mobile -->
<div class="tabesh-ai-browser-overlay" id="tabesh-ai-browser-overlay"></div>

<!-- AI Browser Sidebar -->
<div class="tabesh-ai-browser-sidebar" id="tabesh-ai-browser-sidebar" dir="rtl">
	<!-- Header -->
	<div class="tabesh-ai-browser-header">
		<div class="tabesh-ai-browser-avatar">
			<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
				<rect x="4" y="4" width="16" height="16" rx="2"></rect>
				<circle cx="9" cy="10" r="1" fill="currentColor"></circle>
				<circle cx="15" cy="10" r="1" fill="currentColor"></circle>
				<path d="M9 15h6"></path>
			</svg>
		</div>
		<div class="tabesh-ai-browser-info">
			<h3><?php echo esc_html__( 'دستیار هوشمند تابش', 'tabesh' ); ?></h3>
			<p class="tabesh-ai-browser-status">
				<span class="status-dot"></span>
				<?php echo esc_html__( 'آنلاین', 'tabesh' ); ?>
			</p>
		</div>
		<button class="tabesh-ai-browser-close" id="tabesh-ai-browser-close" aria-label="<?php echo esc_attr__( 'بستن', 'tabesh' ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
				<line x1="18" y1="6" x2="6" y2="18"></line>
				<line x1="6" y1="6" x2="18" y2="18"></line>
			</svg>
		</button>
	</div>

	<!-- Messages Container -->
	<div class="tabesh-ai-browser-messages" id="tabesh-ai-browser-messages">
		<!-- Welcome message will be added by JavaScript -->
	</div>

	<!-- Typing Indicator -->
	<div class="tabesh-ai-browser-typing" style="display: none;">
		<div class="typing-dots">
			<span></span>
			<span></span>
			<span></span>
		</div>
		<span class="typing-text"><?php echo esc_html__( 'در حال نوشتن...', 'tabesh' ); ?></span>
	</div>

	<!-- Input Area -->
	<div class="tabesh-ai-browser-input-wrapper">
		<!-- Quick Actions -->
		<div class="tabesh-ai-browser-quick-actions" id="tabesh-ai-quick-actions">
			<!-- Quick action buttons will be added by JavaScript -->
		</div>

		<!-- Input Form -->
		<form class="tabesh-ai-browser-input-form" id="tabesh-ai-browser-form">
			<textarea 
				id="tabesh-ai-browser-input" 
				class="tabesh-ai-browser-input"
				placeholder="<?php echo esc_attr__( 'پیام خود را بنویسید...', 'tabesh' ); ?>"
				rows="1"
			></textarea>
			<button type="submit" class="tabesh-ai-browser-send" aria-label="<?php echo esc_attr__( 'ارسال', 'tabesh' ); ?>">
				<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
					<line x1="22" y1="2" x2="11" y2="13"></line>
					<polygon points="22 2 15 22 11 13 2 9 22 2"></polygon>
				</svg>
			</button>
		</form>
	</div>
</div>
