const { ethers } = require("ethers");

// Command Line Input
const privateKey = process.argv[2];
const tokenAddress = process.argv[3];
const amount = process.argv[4];
const decimals = process.argv[5];

// RPC
const RPC_URL = "https://bsc-dataseed.binance.org/";

// PancakeSwap Router
const ROUTER_ADDRESS =
  "0x10ED43C718714eb63d5aA57B78B54704E256024E";

// WBNB
const WBNB =
  "0xbb4CdB9CBd36B01bD1cBaEBF2De08d9173bc095c";

const routerAbi = [
  "function getAmountsOut(uint amountIn, address[] memory path) public view returns (uint[] memory amounts)",

  "function swapExactETHForTokensSupportingFeeOnTransferTokens(" +
    "uint amountOutMin," +
    "address[] calldata path," +
    "address to," +
    "uint deadline" +
    ") external payable"
];

async function main() {
  try {
    // Provider
    const provider =
      new ethers.providers.JsonRpcProvider(
        RPC_URL
      );

    // Wallet
    const wallet = new ethers.Wallet(
      privateKey,
      provider
    );

    // Router
    const router = new ethers.Contract(
      ROUTER_ADDRESS,
      routerAbi,
      wallet
    );

    // Amount in BNB
    const amountIn =
      ethers.utils.parseEther(amount);

    // Path
    const path = [WBNB, tokenAddress];

    // Expected Output
    const amounts =
      await router.getAmountsOut(
        amountIn,
        path
      );

    const expectedOut = amounts[1];

    // console.log(
    //   "Expected Token:",
    //   ethers.utils.formatUnits(
    //     expectedOut,
    //     decimals
    //   )
    // );

    // 5% Slippage
    const amountOutMin =
      expectedOut.mul(95).div(100);

    // Deadline
    const deadline =
      Math.floor(Date.now() / 1000) + 60 * 20;

    // Swap
    const tx =
      await router.swapExactETHForTokensSupportingFeeOnTransferTokens(
        amountOutMin,
        path,
        wallet.address,
        deadline,
        {
          value: amountIn,
          gasLimit: 600000
        }
      );

    //console.log("TX Hash:", tx.hash);

    await tx.wait();

    console.log(tx.hash);
  } catch (err) {
    //console.log("ERROR:", err);
    console.log("failed to swap")
  }
}

main();