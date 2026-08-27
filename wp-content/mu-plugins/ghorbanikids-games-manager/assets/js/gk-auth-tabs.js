document.addEventListener("DOMContentLoaded", function() {
    var customerLogin = document.getElementById("customer_login");
    if (!customerLogin) return;

    var col1 = customerLogin.querySelector(".col-1") || customerLogin.querySelector(".u-column1");
    var col2 = customerLogin.querySelector(".col-2") || customerLogin.querySelector(".u-column2");

    if (col1 && col2) {
        if (!document.getElementById("gkAuthTabsHeader")) {
            var tabsHeader = document.createElement("div");
            tabsHeader.id = "gkAuthTabsHeader";
            tabsHeader.className = "gk-auth-tabs-header";
            tabsHeader.innerHTML = `
                <button type="button" class="gk-auth-tab-btn active" data-target="login">🔑 ورود به حساب</button>
                <button type="button" class="gk-auth-tab-btn" data-target="register">✨ عضویت و ثبت‌نام</button>
            `;
            customerLogin.parentNode.insertBefore(tabsHeader, customerLogin);

            var tabBtns = tabsHeader.querySelectorAll(".gk-auth-tab-btn");
            tabBtns.forEach(function(btn) {
                btn.addEventListener("click", function(e) {
                    e.preventDefault();
                    tabBtns.forEach(function(b) { b.classList.remove("active"); });
                    btn.classList.add("active");

                    var target = btn.getAttribute("data-target");
                    if (target === "login") {
                        customerLogin.classList.remove("show-register");
                        customerLogin.classList.add("show-login");
                    } else {
                        customerLogin.classList.remove("show-login");
                        customerLogin.classList.add("show-register");
                    }
                });
            });
        }

        // پیش‌فرض نمایش ورود
        customerLogin.classList.remove("show-register");
        customerLogin.classList.add("show-login");
    }
});