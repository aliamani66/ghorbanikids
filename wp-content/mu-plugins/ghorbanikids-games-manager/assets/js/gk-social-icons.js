document.addEventListener("DOMContentLoaded", function() {
    var socialHtml = `
        <div class="gk-social-replacement-box">
            <div class="gk-social-links-row">
                <a href="https://instagram.com/ghorbanikids" target="_blank" rel="noopener" class="gk-s-btn gk-s-instagram" title="اینستاگرام: @ghorbanikids">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                        <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                        <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                    </svg>
                </a>
                <a href="https://t.me/ghorbanikids" target="_blank" rel="noopener" class="gk-s-btn gk-s-telegram" title="تلگرام: @ghorbanikids">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.14-.07-.19-.08-.05-.19-.02-.27 0-.12.03-1.99 1.27-5.61 3.72-.53.36-1.01.54-1.44.53-.47-.01-1.38-.27-2.05-.49-.83-.27-1.49-.42-1.43-.88.03-.24.37-.49 1.02-.75 3.98-1.73 6.64-2.88 7.97-3.44 3.8-1.58 4.59-1.86 5.1-1.87.11 0 .37.03.54.17.14.12.18.28.2.45-.02.07-.02.21-.04.38z"/>
                    </svg>
                </a>
                <a href="https://ble.ir/ghorbanikids" target="_blank" rel="noopener" class="gk-s-btn gk-s-bale" title="پیام‌رسان بله: @ghorbanikids">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor">
                        <rect width="24" height="24" rx="6" fill="#14b8a6"/>
                        <path d="M7 6h10a1 1 0 0 1 1 1v6a5 5 0 0 1-5 5H7a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1zm3 8h4a2 2 0 0 0 2-2V9H10v5z" fill="#ffffff"/>
                    </svg>
                </a>
            </div>
            <div class="gk-social-phone-row">
                <a href="tel:09306197877" class="gk-s-phone-box" title="تماس مستقیم و پشتیبانی">
                    <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                    </svg>
                    <span>۰۹۳۰ ۶۱۹ ۷۸۷۷</span>
                </a>
            </div>
        </div>
    `;

    // جایگزینی در سایدبار دسکتاپ
    var target = document.querySelector("#side-header .fusion-social-links-header") || 
                 document.querySelector("#side-header .fusion-social-networks") ||
                 document.querySelector("#side-header .fusion-social-networks-wrapper") ||
                 document.querySelector("#side-header .fusion-builder-column:last-child .fusion-column-wrapper");
    if (target) {
        target.innerHTML = socialHtml;
    }

    // درج در منوی موبایل
    var mobileNav = document.querySelector(".fusion-mobile-nav-holder") || document.querySelector(".awb-menu__mobile-container");
    if (mobileNav && !document.getElementById("gkMobileSocialInjected")) {
        var mobileDiv = document.createElement("div");
        mobileDiv.id = "gkMobileSocialInjected";
        mobileDiv.style.padding = "15px 0";
        mobileDiv.innerHTML = socialHtml;
        mobileNav.appendChild(mobileDiv);
    }

    // حذف باکس‌های اشتراک‌گذاری قدیمی
    var shareBoxes = document.querySelectorAll(".fusion-sharing-box, .fusion-social-sharing, .sharing-box");
    shareBoxes.forEach(function(box) {
        box.remove();
    });
});