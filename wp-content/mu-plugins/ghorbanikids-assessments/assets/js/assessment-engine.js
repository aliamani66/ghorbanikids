/**
 * Ghorbani Kids Assessment Engine JS
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        var $quiz = $('#gk-quiz-app');
        if (!$quiz.length) return;

        var currentStep = 0;
        var $slides = $('.gk-question-slide');
        var totalSteps = $slides.length;
        var userAnswers = {};
        var quizSlug = $quiz.data('slug');
        var isLocked = false;

        // کلیک روی گزینه
        $quiz.on('click', '.gk-option-card', function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (isLocked) return;

            var $card = $(this);
            var $slide = $card.closest('.gk-question-slide');
            var qid = parseInt($slide.data('qid'), 10);
            var val = parseInt($card.data('val'), 10);

            $slide.find('.gk-option-card').removeClass('selected');
            $card.addClass('selected');

            userAnswers[qid] = val;
            updateProgress();

            // قفل کردن کلیک برای جلوگیری از جهش چندباره
            isLocked = true;

            if (currentStep < totalSteps - 1) {
                setTimeout(function() {
                    goToStep(currentStep + 1);
                    isLocked = false;
                }, 280);
            } else {
                $('#gk-btn-next').hide();
                $('#gk-btn-submit').show();
                isLocked = false;
            }
        });

        // دکمه بعدی دستی
        $('#gk-btn-next').on('click', function(e) {
            e.preventDefault();
            if (isLocked) return;

            var $currentSlide = $($slides[currentStep]);
            var qid = parseInt($currentSlide.data('qid'), 10);

            if (!userAnswers[qid]) {
                alert('لطفاً ابتدا یکی از گزینه‌ها را انتخاب نمایید.');
                return;
            }

            if (currentStep < totalSteps - 1) {
                goToStep(currentStep + 1);
            }
        });

        // دکمه قبلی
        $('#gk-btn-prev').on('click', function(e) {
            e.preventDefault();
            if (isLocked) return;

            if (currentStep > 0) {
                goToStep(currentStep - 1);
            }
        });

        function goToStep(step) {
            if (step < 0 || step >= totalSteps) return;

            $slides.removeClass('active').hide();
            $($slides[step]).addClass('active').show();

            currentStep = step;

            $('#gk-current-step').text(currentStep + 1);

            if (currentStep === 0) {
                $('#gk-btn-prev').hide();
            } else {
                $('#gk-btn-prev').show();
            }

            if (currentStep === totalSteps - 1) {
                var qid = parseInt($($slides[currentStep]).data('qid'), 10);
                if (userAnswers[qid]) {
                    $('#gk-btn-next').hide();
                    $('#gk-btn-submit').show();
                } else {
                    $('#gk-btn-next').show();
                    $('#gk-btn-submit').hide();
                }
            } else {
                $('#gk-btn-next').show();
                $('#gk-btn-submit').hide();
            }

            updateProgress();
        }

        function updateProgress() {
            var answeredCount = Object.keys(userAnswers).length;
            var pct = Math.round((answeredCount / totalSteps) * 100);

            $('#gk-progress-fill').css('width', pct + '%');
            $('#gk-progress-text').text(pct + '٪');
        }

        // ارسال نهایی فرم و دریافت کارنامه
        $('#gk-btn-submit').on('click', function(e) {
            e.preventDefault();

            var childName = $('#gk-child-name').val().trim();
            if (!childName) {
                childName = 'فرزند عزیز';
            }

            var childAge = $('#gk-child-age').val();

            var answeredCount = Object.keys(userAnswers).length;
            if (answeredCount < totalSteps) {
                alert('لطفاً به تمام سوالات آزمون پاسخ دهید.');
                return;
            }

            $('.gk-quiz-header, .gk-progress-wrapper, .gk-questions-deck, .gk-quiz-nav').hide();
            $('#gk-loading-overlay').fadeIn(300);

            $.ajax({
                url: gkAssessmentData.ajax_url,
                type: 'POST',
                data: {
                    action: 'gk_submit_assessment',
                    nonce: gkAssessmentData.nonce,
                    slug: quizSlug,
                    child_name: childName,
                    child_age: childAge,
                    answers: userAnswers
                },
                success: function(res) {
                    $('#gk-loading-overlay').hide();
                    if (res.success) {
                        var $reportContainer = $('#gk-report-container');
                        $reportContainer.html(res.data.report_html).show();

                        setTimeout(function() {
                            initRadarChart(res.data.chart_labels, res.data.chart_data);
                        }, 100);

                        $('html, body').animate({
                            scrollTop: $reportContainer.offset().top - 40
                        }, 500);
                    } else {
                        alert(res.data.message || 'خطایی در پردازش اطلاعات رخ داد.');
                        $('.gk-quiz-header, .gk-progress-wrapper, .gk-questions-deck, .gk-quiz-nav').show();
                    }
                },
                error: function() {
                    $('#gk-loading-overlay').hide();
                    alert('خطا در برقراری ارتباط با سرور. لطفاً مجدداً تلاش نمایید.');
                    $('.gk-quiz-header, .gk-progress-wrapper, .gk-questions-deck, .gk-quiz-nav').show();
                }
            });
        });

        function initRadarChart(labels, dataValues) {
            var canvas = document.getElementById('gk-radar-chart');
            if (!canvas) return;

            var ctx = canvas.getContext('2d');
            if (!ctx) return;

            var chartType = labels.length > 3 ? 'radar' : 'bar';

            new Chart(ctx, {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'درصد توانمندی (٪)',
                        data: dataValues,
                        backgroundColor: chartType === 'radar' ? 'rgba(108, 92, 231, 0.25)' : 'rgba(108, 92, 231, 0.8)',
                        borderColor: '#6c5ce7',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#8526ff',
                        pointBorderColor: '#fff',
                        pointRadius: 5,
                        pointHoverRadius: 7
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    scales: chartType === 'radar' ? {
                        r: {
                            angleLines: { color: '#e2e8f0' },
                            grid: { color: '#e2e8f0' },
                            suggestedMin: 0,
                            suggestedMax: 100,
                            ticks: { stepSize: 25, font: { family: 'Tahoma', size: 11 }, backdropColor: 'transparent' },
                            pointLabels: { font: { family: 'Tahoma', size: 13, weight: 'bold' }, color: '#1e293b' }
                        }
                    } : {
                        y: { beginAtZero: true, max: 100, ticks: { font: { family: 'Tahoma' } } },
                        x: { ticks: { font: { family: 'Tahoma', weight: 'bold' } } }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    });
})(jQuery);