const { ethers } = require("ethers");

// ======================
// INPUTS
// ======================

const privateKey = process.argv[2];
const tokenIn = process.argv[3];
const tokenOut = process.argv[4];
const amount = process.argv[5];
const inputDecimals = process.argv[6];

// ======================
// CONFIG
// ======================

const provider = new ethers.providers.JsonRpcProvider(
    "https://bsc-dataseed.binance.org/"
);

const wallet = new ethers.Wallet(privateKey, provider);

const routerAddress =
    "0x10ED43C718714eb63d5aA57B78B54704E256024E";

const routerAbi = [
    "function getAmountsOut(uint amountIn, address[] memory path) view returns (uint[] memory amounts)",
    "function swapExactTokensForTokens(uint amountIn,uint amountOutMin,address[] calldata path,address to,uint deadline) returns (uint[] memory amounts)"
];

const tokenAbi = [
    "function approve(address spender, uint amount) returns (bool)"
];

const wbnb = "0xbb4CdB9CBd36B01bD1cBaEBF2De08d9173bc095c";

// ======================
// MAIN
// ======================

async function swap() {

    try {

        const router = new ethers.Contract(routerAddress, routerAbi, wallet);
        const token = new ethers.Contract(tokenIn, tokenAbi, wallet);

        const amountIn = ethers.utils.parseUnits(amount, inputDecimals);

        // ----------------------
        // APPROVE
        // ----------------------
        const approveTx = await token.approve(routerAddress, amountIn);
        await approveTx.wait();

        //console.log("Approved");

        // ----------------------
        // AUTO ROUTE (IMPORTANT)
        // ----------------------

        let path = [tokenIn, tokenOut];

        // try direct first, if fail fallback WBNB route
        let amounts;

        try {
            amounts = await router.getAmountsOut(amountIn, path);
        } catch (e1) {
            //console.log("Direct pair not found, using WBNB route...");
            path = [tokenIn, wbnb, tokenOut];
            amounts = await router.getAmountsOut(amountIn, path);
        }

        // ----------------------
        // SLIPPAGE 10%
        // ----------------------

        const expectedOut = amounts[amounts.length - 1];
        const amountOutMin = expectedOut.mul(90).div(100);

        const deadline = Math.floor(Date.now() / 1000) + 60 * 20;

        // ----------------------
        // SWAP
        // ----------------------

        const tx = await router.swapExactTokensForTokens(
            amountIn,
            amountOutMin,
            path,
            wallet.address,
            deadline,
            { gasLimit: 700000 }
        );

        //console.log("TX SENT:", tx.hash);

        const receipt = await tx.wait();

        //console.log("SUCCESS:", receipt.transactionHash);

        console.log(receipt.transactionHash);

    } catch (err) {

        //console.log("FAILED:", err.reason || err.message);

        console.log('Failed to swap');
    }
}

swap();