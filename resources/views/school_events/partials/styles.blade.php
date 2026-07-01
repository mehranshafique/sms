<style>
    .se-hero {
        border-radius: 18px;
        background: linear-gradient(120deg, #0b2a6b 0%, #13386e 50%, #2563eb 100%);
        position: relative;
        overflow: hidden;
    }
    .se-hero::after {
        content: "";
        position: absolute;
        right: -40px;
        top: -60px;
        width: 220px;
        height: 220px;
        background: rgba(255,255,255,.08);
        border-radius: 50%;
    }
    .se-hero__icon { font-size: 3.2rem; color: rgba(255,255,255,.35); }
    .se-stat {
        background: #fff;
        border: 1px solid #eef0f4;
        border-radius: 14px;
        padding: 18px;
        height: 100%;
    }
    .se-stat__icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        color: #fff;
        margin-bottom: 10px;
    }
    [data-theme="dark"] .se-stat { background: #1e2746; border-color: #2b365c; color: #e8ebf5; }
</style>
