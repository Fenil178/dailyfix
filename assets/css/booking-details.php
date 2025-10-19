
        .custom-modal { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(5px); -webkit-backdrop-filter: blur(5px); align-items: center; justify-content: center; }
        .custom-modal.show { display: flex; }
        .custom-modal-content { background-color: var(--background-color-card); padding: 30px; border-radius: 12px; width: 90%; max-width: 420px; text-align: center; box-shadow: 0 8px 30px rgba(0,0,0,0.25); animation: modal-fade-in 0.3s ease-out; position: relative; }
        @keyframes modal-fade-in { from { opacity: 0; transform: translateY(-30px) scale(0.95); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .modal-icon-custom { font-size: 3rem; margin-bottom: 1rem; }
        .modal-icon-custom.success { color: #10b981; } .modal-icon-custom.error { color: #ef4444; } .modal-icon-custom.warning { color: #f59e0b; }
        .custom-modal-content h3 { color: var(--text-color-dark); font-size: 1.5rem; margin-bottom: 0.5rem; }
        .custom-modal-content p { color: var(--text-color-light); margin-bottom: 1.5rem; line-height: 1.6; }
        .custom-modal-content .ok-btn { background-color: var(--primary-color); color: #fff; padding: 10px 20px; border: none; border-radius: 8px; cursor: pointer; width: 100px; }
        body.dark-mode .custom-modal-content .ok-btn { color: #111; }
        .modal-close-icon { position: absolute; top: 10px; right: 15px; font-size: 1.5rem; background: none; border: none; cursor: pointer; color: var(--text-color-light); padding: 5px; }
        .modal-buttons-container { display: flex; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
        .modal-buttons-container .btn { padding: 10px 25px; font-weight: 600; min-width: 120px; border-radius: 8px; }
        .modal-buttons-container .btn-secondary { background-color: var(--hover-color); border: 1px solid var(--border-color); color: var(--text-color-light); }
        .modal-buttons-container .btn-secondary:hover { background-color: var(--border-color); }
        .modal-buttons-container .btn-primary { background-color: var(--primary-color); color: #fff; }
        body.dark-mode .modal-buttons-container .btn-primary { color: #111; }
         /* Coupon Specific Styles */
        #coupon-section label { font-weight: 500; display: block; margin-bottom: 0.5rem; color: var(--text-color-dark); }
        #coupon-section input[type="text"] { flex-grow: 1; padding: 10px; border-radius: 6px; border: 1px solid var(--border-color); background-color: var(--hover-color); color: var(--text-color-dark);}
        #coupon-section button#apply-coupon-btn { padding: 10px 15px; flex-shrink: 0; background-color: var(--secondary-color); color: var(--text-color-dark); border:none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background-color 0.2s, color 0.2s, opacity 0.2s; }
        #coupon-section button#apply-coupon-btn:disabled { opacity: 0.7; cursor: not-allowed; }
        #coupon-section button#apply-coupon-btn:hover:not(:disabled) { background-color: #d97706; }
        #coupon-message { font-size: 0.85rem; margin-top: 0.5rem; min-height: 1.2em; font-weight: 500;}
        #price-summary { margin-top: 1rem; font-size: 0.9rem; color: var(--text-color-light); border-top: 1px dashed var(--border-color); padding-top: 1rem; display: none; }
        #price-summary p { margin: 0.3rem 0; }
        #price-summary hr { border: none; border-top: 1px solid var(--border-color); margin: 0.5rem 0; }
        #price-summary #discount-applied { color: var(--success-color); font-weight: 500; }
        #price-summary #final-cost-display { font-weight: bold; font-size: 1.1em; color: var(--text-color-dark); }
         body.dark-mode #coupon-section input[type="text"] { background-color: #333; border-color: #555; }
         body.dark-mode #coupon-section button#apply-coupon-btn { color: #111; }
         /* Styles for Available Offer Buttons */
         .available-offer-btn {
                background-color: var(--hover-color); border: 1px dashed var(--primary-color); color: var(--primary-color);
                padding: 5px 10px; border-radius: 6px; font-size: 0.8em; cursor: pointer; transition: background-color 0.2s, color 0.2s;
            }
         .available-offer-btn code { background: rgba(0,0,0,0.05); padding: 2px 4px; border-radius: 3px; font-weight: bold;}
         .available-offer-btn:hover { background-color: var(--primary-color); color: white; }
         body.dark-mode .available-offer-btn { background-color: rgba(251, 191, 36, 0.1); border-color: var(--primary-color); color: var(--primary-color); }
         body.dark-mode .available-offer-btn:hover { background-color: var(--primary-color); color: #111; }
         body.dark-mode .available-offer-btn code { background: rgba(255,255,255,0.1); }
